<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * ParserUUEncodedPartProxyTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(ParserUUEncodedPartProxy::class)]
#[Group('Parser')]
#[Group('ParserUUEncodedPartProxy')]
class ParserUUEncodedPartProxyTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $headerContainer;

    // @phpstan-ignore-next-line
    private $partBuilder;

    // @phpstan-ignore-next-line
    private $parser;

    // @phpstan-ignore-next-line
    private $parent;

    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $this->headerContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\UUEncodedPartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\IParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parent = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserNonMimeMessageProxy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new ParserUUEncodedPartProxy(
            $this->partBuilder,
            $this->parser
        );
    }

    public function testGetNextPartStart() : void
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('getNextPartStart')
            ->willReturn(42);
        $this->assertSame(42, $this->instance->getNextPartStart());
    }

    public function testGetNextPartMode() : void
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('getNextPartMode')
            ->willReturn(42);
        $this->assertSame(42, $this->instance->getNextPartMode());
    }

    public function testGetNextPartFilename() : void
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('getNextPartFilename')
            ->willReturn('t00000st');
        $this->assertSame('t00000st', $this->instance->getNextPartFilename());
    }

    public function testSetNextPartStart() : void
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('setNextPartStart')
            ->with(42);
        $this->instance->setNextPartStart(42);
    }

    public function testSetNextPartMode() : void
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('setNextPartMode')
            ->with(42);
        $this->instance->setNextPartMode(42);
    }

    public function testSetNextPartFilename() : void
    {
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('setNextPartFilename')
            ->with('t00stee');
        $this->instance->setNextPartFilename('t00stee');
    }

    public function testGetUnixFileMode() : void
    {
        $this->partBuilder->expects($this->once())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);
        $this->headerContainer->expects($this->once())
            ->method('getUnixFileMode')
            ->willReturn(42);
        $this->assertSame(42, $this->instance->getUnixFileMode());
    }

    public function testGetFilename() : void
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
