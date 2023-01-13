<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * Description of DateConsumerTest
 *
 * @group Consumers
 * @group DateConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\DateConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class DateConsumerTest extends TestCase
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
        $mlpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $cs = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\ConsumerService::class)
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
        $this->dateConsumer = new DateConsumer($cs, $pf);
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

    public function testConsumeDateWithComment() : void
    {
        $dateTest = 'Wed, 17 May 2000 19:08:29 -0400 (some comment)';
        $actDate = 'Wed, 17 May 2000 19:08:29 -0400';
        $ret = $this->dateConsumer->__invoke($dateTest);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals($actDate, $ret[0]->getValue());
        $this->assertEquals($actDate, $ret[0]->getDateTime()->format(DateTime::RFC2822));
    }
}
