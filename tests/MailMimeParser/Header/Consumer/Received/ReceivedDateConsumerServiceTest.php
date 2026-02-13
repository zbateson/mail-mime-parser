<?php

namespace ZBateson\MailMimeParser\Header\Consumer\Received;

use DateTime;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService;

/**
 * Description of ReceivedDateConsumerServiceTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(ReceivedDateConsumerService::class)]
#[CoversClass(AbstractConsumerService::class)]
#[Group('Consumers')]
#[Group('ReceivedDateConsumerService')]
class ReceivedDateConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $dateConsumer;

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->onlyMethods([])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->onlyMethods([])
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->onlyMethods([])
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->onlyMethods([])
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $qscs])
            ->onlyMethods([])
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
