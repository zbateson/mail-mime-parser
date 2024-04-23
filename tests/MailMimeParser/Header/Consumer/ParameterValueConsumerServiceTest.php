<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Part\ContainerPart;

/**
 * Description of ParameterValueConsumerServiceTest
 *
 * @group Consumers
 * @group ParameterValueConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\ParameterValueConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class ParameterValueConsumerServiceTest extends TestCase
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
        $qsmlpcs = $this->getMockBuilder(QuotedStringMimeLiteralPartConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->setMethods()
            ->getMock();
        $this->consumer = new ParameterValueConsumerService($this->logger, $mpf, $ccs, $qsmlpcs);
    }

    public function testConsumeValue() : void
    {
        $ret = $this->consumer->__invoke('value');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ContainerPart::class, $ret[0]);
        $this->assertEquals('value', $ret[0]->getValue());
    }

    public function testConsumeValueToEndToken() : void
    {
        $ret = $this->consumer->__invoke('value; continues');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ContainerPart::class, $ret[0]);
        $this->assertEquals('value', $ret[0]->getValue());
    }

    public function testValueWithComments() : void
    {
        $ret = $this->consumer->__invoke('value (some ; comment)');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ContainerPart::class, $ret[0]);
        $this->assertEquals('value', $ret[0]->getValue());
        $comments = $ret[0]->getComments();
        $this->assertNotEmpty($comments);
        $this->assertCount(1, $comments);
        $this->assertEquals('some ; comment', $comments[0]->getComment());
    }

    public function testWithQuotedValue() : void
    {
        $ret = $this->consumer->__invoke('"quoted; value"');
        $this->assertEquals('quoted; value', $ret[0]->getValue());
    }

    public function testWithHeaderEncodedValue() : void
    {
        $ret = $this->consumer->__invoke('=?US-ASCII?Q?value?=');
        $this->assertEquals('value', $ret[0]->getValue());
    }

    public function testWithQuotedHeaderEncodedValue() : void
    {
        $ret = $this->consumer->__invoke('"=?US-ASCII?Q?value?="');
        $this->assertEquals('value', $ret[0]->getValue());
    }
}
