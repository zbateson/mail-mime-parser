<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Part\ContainerPart;
use ZBateson\MailMimeParser\Header\Part\ParameterPart;

/**
 * Description of ParameterNameValueConsumerServiceTest
 *
 * @group Consumers
 * @group ParameterNameValueConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\ParameterNameValueConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class ParameterNameValueConsumerServiceTest extends TestCase
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
        $pvcs = $this->getMockBuilder(ParameterValueConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $ccs, $qsmlpcs])
            ->setMethods()
            ->getMock();
        $this->consumer = new ParameterNameValueConsumerService($this->logger, $mpf, $pvcs, $ccs, $qscs);
    }

    public function testConsumePartWithoutValue() : void
    {
        $ret = $this->consumer->__invoke('text/html');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ContainerPart::class, $ret[0]);
        $this->assertEquals('text/html', $ret[0]->getValue());
    }

    public function testConsumePartToEndToken() : void
    {
        $ret = $this->consumer->__invoke('text/html; other');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ContainerPart::class, $ret[0]);
        $this->assertEquals('text/html', $ret[0]->getValue());
    }

    public function testNameValue() : void
    {
        $ret = $this->consumer->__invoke('name=value');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ParameterPart::class, $ret[0]);
        $this->assertEquals('name', $ret[0]->getName());
        $this->assertEquals('value', $ret[0]->getValue());
    }

    public function testNameValueToEndToken() : void
    {
        $ret = $this->consumer->__invoke('name=value; other stuff');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ParameterPart::class, $ret[0]);
        $this->assertEquals('name', $ret[0]->getName());
        $this->assertEquals('value', $ret[0]->getValue());
    }

    public function testNameValueWithComments() : void
    {
        $ret = $this->consumer->__invoke('name (comment)=value (other comment)');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ParameterPart::class, $ret[0]);
        $this->assertEquals('name', $ret[0]->getName());
        $this->assertEquals('value', $ret[0]->getValue());
    }

    public function testWithQuotedValue() : void
    {
        $ret = $this->consumer->__invoke('name="quoted value"');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ParameterPart::class, $ret[0]);
        $this->assertEquals('name', $ret[0]->getName());
        $this->assertEquals('quoted value', $ret[0]->getValue());
    }

    public function testWithHeaderEncodedValue() : void
    {
        $ret = $this->consumer->__invoke('name==?US-ASCII?Q?value?=');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ParameterPart::class, $ret[0]);
        $this->assertEquals('name', $ret[0]->getName());
        $this->assertEquals('value', $ret[0]->getValue());
    }

    public function testWithQuotedHeaderEncodedValue() : void
    {
        $ret = $this->consumer->__invoke('name="=?US-ASCII?Q?value?="');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf(ParameterPart::class, $ret[0]);
        $this->assertEquals('name', $ret[0]->getName());
        $this->assertEquals('value', $ret[0]->getValue());
    }
}
