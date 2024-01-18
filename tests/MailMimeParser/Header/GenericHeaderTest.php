<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;

/**
 * Description of GenericHeaderTest
 *
 * @group Headers
 * @group GenericHeader
 * @covers ZBateson\MailMimeParser\Header\GenericHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class GenericHeaderTest extends TestCase
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
        $this->consumerService = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\GenericConsumerMimeLiteralPartService::class)
            ->setConstructorArgs([$mpf, $ccs, $qscs])
            ->setMethods(['__toString'])
            ->getMock();
    }

    public function testParsing() : void
    {
        $header = new GenericHeader($this->consumerService, 'Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
        $this->assertEquals('Hunter S. Thompson', $header->getRawValue());
        $this->assertCount(1, $header->getParts());
        $this->assertEquals('Hunted-By', $header->getName());
    }

    public function testMultilineMimeParts() : void
    {
        $header = new GenericHeader($this->consumerService, 'Hunted-By', '=?US-ASCII?Q?Hunt?=
             =?US-ASCII?Q?er_S._?=
             =?US-ASCII?Q?Thompson?=');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
    }

    /**
     *
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer::isStartToken
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer::isEndToken
     */
    public function testQuotesMimeAndComments() : void
    {
        $header = new GenericHeader(
            $this->consumerService,
            'Actor',
            '"Dwayne \"The Rock\"" =?US-ASCII?Q?Jackson?= (main actor)'
        );
        $this->assertEquals('Dwayne "The Rock" Jackson', $header->getValue());
    }

    public function testCommentBetweenParts() : void
    {
        $header = new GenericHeader(
            $this->consumerService,
            'Actor',
            'Dwayne (The Rock) Jackson'
        );
        $this->assertEquals('Dwayne Jackson', $header->getValue());
    }

    public function testGenericHeaderToString() : void
    {
        $header = new GenericHeader($this->consumerService, 'Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunted-By: Hunter S. Thompson', $header);
    }

    public function testErrorLoggingContextName() : void
    {
        $header = new GenericHeader($this->consumerService, 'Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Header::Hunted-By', $header->getErrorLoggingContextName());
    }

    public function testValidation() : void
    {
        $header = new GenericHeader($this->consumerService, '', '');
        $errs = $header->getErrors(false, LogLevel::NOTICE);
        $this->assertCount(0, $errs);
        $errs = $header->getErrors(true, LogLevel::NOTICE);
        $this->assertCount(2, $errs);
        $this->assertEquals('Header doesn\'t have a name', $errs[0]->getMessage());
        $this->assertEquals(LogLevel::ERROR, $errs[0]->getPsrLevel());
        $this->assertEquals('Header doesn\'t have a value', $errs[1]->getMessage());
        $this->assertEquals(LogLevel::NOTICE, $errs[1]->getPsrLevel());
    }
}
