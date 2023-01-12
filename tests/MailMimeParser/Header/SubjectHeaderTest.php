<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;

/**
 * Description of SubjectHeader
 *
 * @group Headers
 * @group SubjectHeader
 * @covers ZBateson\MailMimeParser\Header\SubjectHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class SubjectHeaderTest extends TestCase
{
    protected $consumerService;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder('ZBateson\MbWrapper\MbWrapper')
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\HeaderPartFactory')
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mlpf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory')
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $this->consumerService = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Consumer\ConsumerService')
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
    }

    public function testParsing()
    {
        $header = new SubjectHeader($this->consumerService, 'Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
        $this->assertEquals('Hunter S. Thompson', $header->getRawValue());
        $this->assertCount(1, $header->getParts());
        $this->assertEquals('Hunted-By', $header->getName());
    }

    public function testMultilineMimeParts()
    {
        $header = new SubjectHeader($this->consumerService, 'Hunted-By', '=?US-ASCII?Q?Hunt?=
             =?US-ASCII?Q?er_S._?=
             =?US-ASCII?Q?Thompson?=');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
    }

    public function testMultilineMimePartsWithTextAtTheEnd()
    {
        $header = new SubjectHeader($this->consumerService, 'Hunted-By', "Hunt=?UTF-8?Q?er_S._Th?=\r\n=?UTF-8?Q?ompson?= Jr.");
        $this->assertEquals('Hunter S. Thompson Jr.', $header->getValue());
    }

    public function testMultilineMimePartWithParentheses()
    {
        $header = new SubjectHeader($this->consumerService, 'Hunted-By', ' =?koi8-r?B?9MXIzsnexdPLycUg0sHCz9TZIChFUlAg58HMwcvUycvBIMkg79TexdTZIPTk?=
            =?koi8-r?Q?)?=');
        $this->assertEquals('Технические работы (ERP Галактика и Отчеты ТД)', $header->getValue());
    }

    /**
     *
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer::isStartToken
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer::isEndToken
     */
    public function testQuotesMimeAndComments()
    {
        $header = new SubjectHeader(
            $this->consumerService,
            'Actor',
            '"Dwayne \"The Rock\"" =?US-ASCII?Q?Johnson?= (main actor)'
        );
        $this->assertEquals('"Dwayne \"The Rock\"" Johnson (main actor)', $header->getValue());
    }

    public function testQuotedMimeEncodedPart()
    {
        $header = new SubjectHeader(
            $this->consumerService,
            'Actor',
            '"=?US-ASCII?Q?Johnson?="'
        );
        $this->assertEquals('"Johnson"', $header->getValue());
    }

    public function testCommentBetweenParts()
    {
        $header = new SubjectHeader(
            $this->consumerService,
            'Actor',
            'Dwayne (The Rock) Jackson'
        );
        $this->assertEquals('Dwayne (The Rock) Jackson', $header->getValue());
    }

    public function testWhiteSpace()
    {
        $header = new SubjectHeader(
            $this->consumerService,
            'Actor',
            'Dwayne  Double Spaced  Jackson'
        );
        $this->assertEquals('Dwayne  Double Spaced  Jackson', $header->getValue());
    }

    public function testMultilineWhiteSpace()
    {
        $header = new SubjectHeader(
            $this->consumerService,
            'Actor',
            "Dwayne\n  Double Spaced  Jackson"
        );
        $this->assertEquals('Dwayne Double Spaced  Jackson', $header->getValue());
    }

    public function testSubjectHeaderToString()
    {
        $header = new SubjectHeader($this->consumerService, 'Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunted-By: Hunter S. Thompson', $header);
    }
}
