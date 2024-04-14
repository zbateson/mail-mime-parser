<?php

namespace ZBateson\MailMimeParser\Header;

use Psr\Log\NullLogger;
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
    // @phpstan-ignore-next-line
    protected $consumerService;
    private $logger;

    protected function setUp() : void
    {
        $this->logger = new NullLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods()
            ->getMock();
        $mlpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $this->consumerService = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\SubjectConsumerService::class)
            ->setConstructorArgs([$this->logger, $mlpf])
            ->setMethods()
            ->getMock();
    }

    private function newSubjectHeader($name, $value)
    {
        return new SubjectHeader($name, $value, $this->logger, $this->consumerService);
    }

    public function testParsing() : void
    {
        $header = $this->newSubjectHeader('Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
        $this->assertEquals('Hunter S. Thompson', $header->getRawValue());
        $this->assertCount(1, $header->getParts());
        $this->assertEquals('Hunted-By', $header->getName());
    }

    public function testMultilineMimeParts() : void
    {
        $header = $this->newSubjectHeader('Hunted-By', '=?US-ASCII?Q?Hunt?=
             =?US-ASCII?Q?er_S._?=
             =?US-ASCII?Q?Thompson?=');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
    }

    public function testMultilineMimePartsWithTextAtTheEnd() : void
    {
        $header = $this->newSubjectHeader('Hunted-By', "Hunt=?UTF-8?Q?er_S._Th?=\r\n=?UTF-8?Q?ompson?= Jr.");
        $this->assertEquals('Hunter S. Thompson Jr.', $header->getValue());
    }

    public function testMultilineMimePartWithParentheses() : void
    {
        $header = $this->newSubjectHeader('Hunted-By', ' =?koi8-r?B?9MXIzsnexdPLycUg0sHCz9TZIChFUlAg58HMwcvUycvBIMkg79TexdTZIPTk?=
            =?koi8-r?Q?)?=');
        $this->assertEquals('Технические работы (ERP Галактика и Отчеты ТД)', $header->getValue());
    }

    /**
     *
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService::isStartToken
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService::isEndToken
     */
    public function testQuotesMimeAndComments() : void
    {
        $header = $this->newSubjectHeader(
            'Actor',
            '"Dwayne \"The Rock\"" =?US-ASCII?Q?Johnson?= (main actor)'
        );
        $this->assertEquals('"Dwayne \"The Rock\"" Johnson (main actor)', $header->getValue());
    }

    public function testQuotedMimeEncodedPart() : void
    {
        $header = $this->newSubjectHeader(
            'Actor',
            '"=?US-ASCII?Q?Johnson?="'
        );
        $this->assertEquals('"Johnson"', $header->getValue());
    }

    public function testCommentBetweenParts() : void
    {
        $header = $this->newSubjectHeader(
            'Actor',
            'Dwayne (The Rock) Jackson'
        );
        $this->assertEquals('Dwayne (The Rock) Jackson', $header->getValue());
    }

    public function testWhiteSpace() : void
    {
        $header = $this->newSubjectHeader(
            'Actor',
            'Dwayne  Double Spaced  Jackson'
        );
        $this->assertEquals('Dwayne  Double Spaced  Jackson', $header->getValue());
    }

    public function testMultilineWhiteSpace() : void
    {
        $header = $this->newSubjectHeader(
            'Actor',
            "Dwayne\n  Double Spaced  Jackson"
        );
        $this->assertEquals('Dwayne Double Spaced  Jackson', $header->getValue());
    }

    public function testSubjectHeaderToString() : void
    {
        $header = $this->newSubjectHeader('Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunted-By: Hunter S. Thompson', $header);
    }
}
