<?php

namespace ZBateson\MailMimeParser\Header\Consumer\Received;

use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;

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
    private $logger;

    protected function setUp() : void
    {
        $this->logger = new NullLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods()
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->setMethods()
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $qscs])
            ->setMethods()
            ->getMock();
        $this->dateConsumer = new ReceivedDateConsumerService($this->logger, $pf, $ccs, $qscs);
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
