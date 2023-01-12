<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use PHPUnit\Framework\TestCase;

/**
 * ParserUUEncodedPartProxyTest
 *
 * @group Parser
 * @group ParserUUEncodedPartProxy
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserUUEncodedPartProxy
 * @author Zaahid Bateson
 */
class ParserUUEncodedPartProxyTest extends TestCase
{
    private $headerContainer;

    private $partBuilder;

    private $parser;

    private $parent;

    private $instance;

    protected function setUp() : void
    {
        $this->headerContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\UUEncodedPartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\IParser::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->parent = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserNonMimeMessageProxy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new ParserUUEncodedPartProxy(
            $this->partBuilder,
            $this->parser
        );
    }

    public function testGetNextPartStart()
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('getNextPartStart')
            ->willReturn(42);
        $this->assertSame(42, $this->instance->getNextPartStart());
    }

    public function testGetNextPartMode()
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('getNextPartMode')
            ->willReturn(42);
        $this->assertSame(42, $this->instance->getNextPartMode());
    }

    public function testGetNextPartFilename()
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('getNextPartFilename')
            ->willReturn('t00000st');
        $this->assertSame('t00000st', $this->instance->getNextPartFilename());
    }

    public function testSetNextPartStart()
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('setNextPartStart')
            ->with(42);
        $this->instance->setNextPartStart(42);
    }

    public function testSetNextPartMode()
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('setNextPartMode')
            ->with(42);
        $this->instance->setNextPartMode(42);
    }

    public function testSetNextPartFilename()
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('setNextPartFilename')
            ->with('t00stee');
        $this->instance->setNextPartFilename('t00stee');
    }

    public function testGetUnixFileMode()
    {
        $this->partBuilder->expects($this->once())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);
        $this->headerContainer->expects($this->once())
            ->method('getUnixFileMode')
            ->willReturn(42);
        $this->assertSame(42, $this->instance->getUnixFileMode());
    }

    public function testGetFilename()
    {
        $this->partBuilder->expects($this->once())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);
        $this->headerContainer->expects($this->once())
            ->method('getFilename')
            ->willReturn('t00000st');
        $this->assertSame('t00000st', $this->instance->getFilename());
    }
}
