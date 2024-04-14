<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use ZBateson\MailMimeParser\Header\Consumer\GenericConsumerMimeLiteralPartService;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;

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
        $this->consumerService = $this->getMockBuilder(GenericConsumerMimeLiteralPartService::class)
            ->setConstructorArgs([$this->logger, $mpf, $ccs, $qscs])
            ->setMethods()
            ->getMock();
    }

    private function newGenericHeader($name, $value)
    {
        return new GenericHeader($name, $value, $this->logger, $this->consumerService);
    }

    public function testParsing() : void
    {
        $header = $this->newGenericHeader('Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
        $this->assertEquals('Hunter S. Thompson', $header->getRawValue());
        $this->assertCount(1, $header->getParts());
        $this->assertEquals('Hunted-By', $header->getName());
    }

    public function testMultilineMimeParts() : void
    {
        $header = $this->newGenericHeader('Hunted-By', '=?US-ASCII?Q?Hunt?=
             =?US-ASCII?Q?er_S._?=
             =?US-ASCII?Q?Thompson?=');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
    }

    /**
     *
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService::isStartToken
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService::isEndToken
     */
    public function testQuotesMimeAndComments() : void
    {
        $header = $this->newGenericHeader(
            'Actor',
            '"Dwayne \"The Rock\"" =?US-ASCII?Q?Jackson?= (main actor)'
        );
        $this->assertEquals('Dwayne "The Rock" Jackson', $header->getValue());
    }

    public function testCommentBetweenParts() : void
    {
        $header = $this->newGenericHeader(
            'Actor',
            'Dwayne (The Rock) Jackson'
        );
        $this->assertEquals('Dwayne Jackson', $header->getValue());
    }

    public function testGenericHeaderToString() : void
    {
        $header = $this->newGenericHeader('Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunted-By: Hunter S. Thompson', $header);
    }

    public function testErrorLoggingContextName() : void
    {
        $header = $this->newGenericHeader('Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Header::Hunted-By', $header->getErrorLoggingContextName());
    }

    public function testValidation() : void
    {
        $header = $this->newGenericHeader('', '');
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
