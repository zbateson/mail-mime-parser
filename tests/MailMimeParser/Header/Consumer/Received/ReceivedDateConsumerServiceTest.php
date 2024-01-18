<?php

namespace ZBateson\MailMimeParser\Header\Consumer\Received;

use DateTime;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;

/**
 * Description of ReceivedDateConsumerServiceTest
 *
 * @group Consumers
 * @group ReceivedDateConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\Received\ReceivedDateConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class ReceivedDateConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $dateConsumer;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$pf])
            ->setMethods(['__toString'])
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$mpf, $qscs])
            ->setMethods(['__toString'])
            ->getMock();
        $this->dateConsumer = new ReceivedDateConsumerService($pf, $ccs, $qscs);
    }

    public function testConsumeDates() : void
    {
        $date = 'Wed, 17 May 2000 19:08:29 -0400';
        $ret = $this->dateConsumer->__invoke($date);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\DatePart::class, $ret[0]);
        $this->assertEquals($date, $ret[0]->getValue());
        $this->assertEquals($date, $ret[0]->getDateTime()->format(DateTime::RFC2822));
    }
}
