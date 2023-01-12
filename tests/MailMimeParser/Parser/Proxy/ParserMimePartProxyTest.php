<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use PHPUnit\Framework\TestCase;

/**
 * ParserMimePartProxyTest
 *
 * @group Parser
 * @group ParserMimePartProxy
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserMimePartProxy
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserPartProxy
 * @author Zaahid Bateson
 */
class ParserMimePartProxyTest extends TestCase
{
    private $headerContainer;

    private $partBuilder;

    private $parser;

    private $parentParser;

    private $parent;

    protected function setUp() : void
    {
        $hc = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor();
        $this->headerContainer = $hc->getMock();
        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\IParser')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->parentParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\IParser')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->parent = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSetGetPart()
    {
        $part = $this->getMockBuilder('ZBateson\MailMimeParser\Message\IMessagePart')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $instance->setPart($part);
        $this->assertSame($part, $instance->getPart());
    }

    public function testParseContent()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->parser
            ->expects($this->once())
            ->method('parseContent')
            ->with($instance);

        $instance->parseContent();
        $instance->parseContent();
    }

    public function testParseAllParsesContent()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);

        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(false, true, true);
        $this->parser
            ->expects($this->once())
            ->method('parseContent');

        $instance->parseAll();
        $instance->parseAll();
    }

    public function testPopNextChild()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(true);

        $c = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy')
            ->disableOriginalConstructor();
        $first = $c->getMock();
        $second = $c->getMock();
        $third = $c->getMock();

        $first->expects($this->once())->method('parseAll');
        $first->expects($this->any())->method('getPart')->willReturn('first');
        $second->expects($this->once())->method('parseAll');
        $second->expects($this->any())->method('getPart')->willReturn('second');
        $third->expects($this->any())->method('getPart')->willReturn('third');

        $this->parser
            ->expects($this->exactly(4))
            ->method('parseNextChild')
            ->willReturnOnConsecutiveCalls($first, $second, $third, null);

        $this->assertSame('first', $instance->popNextChild());
        $this->assertSame('second', $instance->popNextChild());
        $this->assertSame('third', $instance->popNextChild());
        $this->assertNull($instance->popNextChild());
        $this->assertNull($instance->popNextChild());
    }

    public function testPopNextChildParsesContent()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->parser
            ->expects($this->once())
            ->method('parseContent')
            ->with($instance);
        $this->parser
            ->expects($this->once())
            ->method('parseNextChild')
            ->willReturn(null);

        $this->assertNull($instance->popNextChild());
        $this->assertNull($instance->popNextChild());
    }

    public function testParseAllParsesContentAndChildren()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(true);

        $c = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy')
            ->disableOriginalConstructor();
        $first = $c->getMock();
        $second = $c->getMock();
        $third = $c->getMock();

        $first->expects($this->once())->method('parseAll');
        $second->expects($this->once())->method('parseAll');

        $this->parser
            ->expects($this->exactly(4))
            ->method('parseNextChild')
            ->willReturnOnConsecutiveCalls($first, $second, $third, null);

        $instance->parseAll();
    }

    public function testPopChildrenAfterParseAll()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(true);

        $c = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy')
            ->disableOriginalConstructor();
        $first = $c->getMock();
        $second = $c->getMock();
        $third = $c->getMock();

        $first->expects($this->once())->method('parseAll');
        $first->expects($this->any())->method('getPart')->willReturn('first');
        $second->expects($this->once())->method('parseAll');
        $second->expects($this->any())->method('getPart')->willReturn('second');
        $third->expects($this->any())->method('getPart')->willReturn('third');

        $this->parser
            ->expects($this->exactly(4))
            ->method('parseNextChild')
            ->willReturnOnConsecutiveCalls($first, $second, $third, null);

        $instance->parseAll();
        $this->assertSame('first', $instance->popNextChild());
        $this->assertSame('second', $instance->popNextChild());
        $this->assertSame('third', $instance->popNextChild());
        $this->assertNull($instance->popNextChild());
        $this->assertNull($instance->popNextChild());
    }

    public function testGetHeaderContainer()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder->expects($this->once())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);
        $this->assertSame($this->headerContainer, $instance->getHeaderContainer());
    }

    public function testGetContentType()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder->expects($this->once())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);
        $this->headerContainer->expects($this->any())
            ->method('get')
            ->with($this->equalTo('Content-Type'))
            ->willReturn('Fruity');
        $this->assertSame('Fruity', $instance->getContentType());
    }

    public function testGetMimeBoundary()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);

        $h = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerContainer
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('Content-Type'))
            ->willReturn($h);

        $h->expects($this->any())
            ->method('getValueFor')
            ->with($this->equalTo('boundary'))
            ->willReturn('Personal Space');

        $this->partBuilder->expects($this->once())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);

        $this->assertSame('Personal Space', $instance->getMimeBoundary());
        $this->assertSame('Personal Space', $instance->getMimeBoundary());
    }

    public function testGetNullMimeBoundary()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);

        $this->headerContainer
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(null);

        $this->partBuilder->expects($this->once())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);

        $this->assertNull($instance->getMimeBoundary());
        $this->assertNull($instance->getMimeBoundary());
    }

    public function testSetEndBoundaryFound()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);

        $h = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerContainer
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('Content-Type'))
            ->willReturn($h);
        $h->expects($this->any())
            ->method('getValueFor')
            ->with($this->equalTo('boundary'))
            ->willReturn('Personal Space');

        $this->partBuilder->expects($this->any())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);

        $this->assertFalse($instance->isEndBoundaryFound());
        $this->assertFalse($instance->isParentBoundaryFound());
        $this->assertFalse($instance->setEndBoundaryFound('Not in your personal space'));
        $this->assertTrue($instance->setEndBoundaryFound('--Personal Space'));
        $this->assertFalse($instance->isEndBoundaryFound());
        $this->assertTrue($instance->setEndBoundaryFound('--Personal Space--'));
        $this->assertTrue($instance->isEndBoundaryFound());
        $this->assertFalse($instance->isParentBoundaryFound());
    }

    public function testSetEndBoundaryFoundWithParentBoundary()
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);

        $this->headerContainer
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(null);
        $this->partBuilder->method('getHeaderContainer')->willReturn($this->headerContainer);

        $this->parent->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->partBuilder->method('getParent')->willReturn($this->parent);

        $this->assertFalse($instance->isEndBoundaryFound());
        $this->assertFalse($instance->isParentBoundaryFound());
        $this->assertFalse($instance->setEndBoundaryFound('Not in your personal space'));
        $this->assertTrue($instance->setEndBoundaryFound('--Personal Space'));
        $this->assertFalse($instance->isEndBoundaryFound());
        $this->assertTrue($instance->isParentBoundaryFound());
    }

    public function testSetEof()
    {
        $parentPb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $parent = new ParserMimePartProxy(
            $parentPb,
            $this->parentParser
        );
        $this->partBuilder->method('getParent')->willReturn($parent);
        $instance = new ParserMimePartProxy(
            $this->partBuilder,
            $this->parser
        );

        $this->assertFalse($parent->isParentBoundaryFound());
        $this->assertFalse($parent->isEndBoundaryFound());
        $this->assertFalse($instance->isParentBoundaryFound());
        $this->assertFalse($instance->isEndBoundaryFound());

        $instance->setEof();

        $this->assertTrue($parent->isParentBoundaryFound());
        $this->assertFalse($parent->isEndBoundaryFound());
        $this->assertTrue($instance->isParentBoundaryFound());
        $this->assertFalse($instance->isEndBoundaryFound());
    }

    public function testSetGetLastLineEndingLength()
    {
        $parent = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partBuilder->method('getParent')->willReturn($parent);

        $parent->expects($this->once())
            ->method('setLastLineEndingLength')
            ->with(42);
        $parent->expects($this->once())
            ->method('getLastLineEndingLength')
            ->willReturn(18);

        $instance = new ParserMimePartProxy(
            $this->partBuilder,
            $this->parser
        );

        $instance->setLastLineEndingLength(42);
        $this->assertSame(18, $instance->getLastLineEndingLength());
    }
}
