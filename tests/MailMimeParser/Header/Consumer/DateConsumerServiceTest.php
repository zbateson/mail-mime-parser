<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use DateTime;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;

/**
 * Description of DateConsumerServiceTest
 *
 * @group Consumers
 * @group DateConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\DateConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class DateConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $dateConsumer;
    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
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
        $this->dateConsumer = new DateConsumerService($this->logger, $pf, $ccs, $qscs);
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
        $comments = $ret[0]->getComments();
        $this->assertNotEmpty($comments);
        $this->assertCount(1, $comments);
        $this->assertEquals($actDate, $ret[0]->getValue());
        $this->assertEquals($actDate, $ret[0]->getDateTime()->format(DateTime::RFC2822));
        $this->assertEquals('some comment', $comments[0]->getComment());
    }
}
