<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;

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
        $this->consumerService = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\ConsumerService::class)
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
    }

    public function testSimpleDate() : void
    {
        $header = new DateHeader($this->consumerService, 'Date', 'Wed, 17 May 2000 19:08:29 -0400');
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $header->getValue());
        $dt = $header->getDateTime();
        $this->assertNotNull($dt);
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $dt->format(\DateTime::RFC2822));
    }

    public function testInvalidDate() : void
    {
        $header = new DateHeader($this->consumerService, 'DATE', 'This is not a date');
        $this->assertNull($header->getDateTime());
        $this->assertEquals('This is not a date', $header->getValue());
    }

    public function testDateWithEmptyPart() : void
    {
        $header = new DateHeader($this->consumerService, 'DATE', '');
        $this->assertNull($header->getDateTime());
    }

    public function testDateHeaderToString() : void
    {
        $header = new DateHeader($this->consumerService, 'Date', 'Wed, 17 May 2000 19:08:29 -0400');
        $this->assertEquals('Date: Wed, 17 May 2000 19:08:29 -0400', $header);
    }
}
