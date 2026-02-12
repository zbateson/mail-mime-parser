<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Part\ContainerPart;
use ZBateson\MailMimeParser\Header\Part\ParameterPart;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of ParameterNameValueConsumerServiceTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(ParameterNameValueConsumerService::class)]
#[CoversClass(AbstractConsumerService::class)]
#[Group('Consumers')]
#[Group('ParameterNameValueConsumerService')]
class ParameterNameValueConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $consumer;

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->onlyMethods([])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->onlyMethods([])
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->onlyMethods([])
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->onlyMethods([])
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $qscs])
            ->onlyMethods([])
            ->getMock();
        $qsmlpcs = $this->getMockBuilder(QuotedStringMimeLiteralPartConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->onlyMethods([])
            ->getMock();
        $pvcs = $this->getMockBuilder(ParameterValueConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $ccs, $qsmlpcs])
            ->onlyMethods([])
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
