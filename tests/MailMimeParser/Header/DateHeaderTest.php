<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;

/**
 * Description of DateHeaderTest
 *
 * @group Headers
 * @group DateHeader
 * @covers ZBateson\MailMimeParser\Header\DateHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class DateHeaderTest extends TestCase
{
    // @phpstan-ignore-next-line
    protected $consumerService;

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
        $this->consumerService = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\DateConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, $qscs])
            ->setMethods()
            ->getMock();
    }

    private function newDateHeader($name, $value)
    {
        return new DateHeader($name, $value, $this->logger, $this->consumerService);
    }

    public function testSimpleDate() : void
    {
        $header = $this->newDateHeader('Date', 'Wed, 17 May 2000 19:08:29 -0400');
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $header->getValue());
        $dt = $header->getDateTime();
        $this->assertNotNull($dt);
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $dt->format(\DateTime::RFC2822));
    }

    public function testDateWithNewLine() : void
    {
        $date = 'Wed, 17 May 2000 19:08:29 -0400';
        $header = $this->newDateHeader('Date', "Wed,\r\n  17 May 2000 19:08:29 -0400");
        $this->assertEquals($date, $header->getValue());
        $this->assertFalse($header->hasErrors(), join(', ', array_map(fn ($e) => $e->getMessage(), $header->getAllErrors())));
        $dt = $header->getDateTime();
        $this->assertNotNull($dt);
        $this->assertEquals($date, $dt->format(\DateTime::RFC2822));
    }

    public function testInvalidDate() : void
    {
        $header = $this->newDateHeader('DATE', 'This is not a date');
        $this->assertNull($header->getDateTime());
        $this->assertEquals('This is not a date', $header->getValue());
    }

    public function testDateWithEmptyPart() : void
    {
        $header = $this->newDateHeader('DATE', '');
        $this->assertNull($header->getDateTime());
    }

    public function testDateHeaderToString() : void
    {
        $header = $this->newDateHeader('Date', 'Wed, 17 May 2000 19:08:29 -0400');
        $this->assertEquals('Date: Wed, 17 May 2000 19:08:29 -0400', $header);
    }
}
