<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;

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
        $charsetConverter = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MbWrapperService::class)
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
}
