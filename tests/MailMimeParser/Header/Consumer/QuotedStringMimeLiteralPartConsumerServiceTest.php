<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Part\QuotedLiteralPart;

/**
 * Description of QuotedStringMimeLiteralPartConsumerServiceTest
 *
 * @group Consumers
 * @group QuotedStringMimeLiteralPartConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringMimeLiteralPartConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class QuotedStringMimeLiteralPartConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $consumer;

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
        $this->consumer = new QuotedStringMimeLiteralPartConsumerService($this->logger, $pf);
    }

    public function testConsumeValue() : void
    {
        $ret = $this->consumer->__invoke('value');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(QuotedLiteralPart::class, $ret[0]);
        $this->assertEquals('value', $ret[0]->getValue());
    }

    public function testConsumeMimeEncodedValue() : void
    {
        $ret = $this->consumer->__invoke('=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(QuotedLiteralPart::class, $ret[0]);
        $this->assertEquals('Kilgore Trout', $ret[0]->getValue());
    }

    public function testWithQuotedHeaderMultipleEncodedValues() : void
    {
        $ret = $this->consumer->__invoke('=?US-ASCII?Q?Kilgore?= =?US-ASCII?Q?Trout?=');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('KilgoreTrout', $ret[0]->getValue());
    }

    public function testWithQuotedHeaderMultipleEncodedValuesAndLinesBetween() : void
    {
        $ret = $this->consumer->__invoke("=?US-ASCII?Q?Kilg?= \r\n =?US-ASCII?Q?or?=  =?US-ASCII?Q?e_Trout?=");
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Kilgore Trout', $ret[0]->getValue());
    }
}
