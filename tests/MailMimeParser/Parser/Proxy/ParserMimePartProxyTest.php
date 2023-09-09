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
    // @phpstan-ignore-next-line
    private $headerContainer;

    // @phpstan-ignore-next-line
    private $partBuilder;

    // @phpstan-ignore-next-line
    private $parser;

    // @phpstan-ignore-next-line
    private $parentParser;

    // @phpstan-ignore-next-line
    private $parent;

    protected function setUp() : void
    {
        $hc = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartHeaderContainer::class)
            ->disableOriginalConstructor();
        $this->headerContainer = $hc->getMock();
        $this->partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\IParserService::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->parentParser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\IParserService::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->parent = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSetGetPart() : void
    {
        $part = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\IMessagePart::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $instance->setPart($part);
        $this->assertSame($part, $instance->getPart());
    }

    public function testParseContent() : void
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

    public function testParseAllParsesContent() : void
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

    public function testPopNextChild() : void
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(true);

        $c = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
            ->disableOriginalConstructor();
        $first = $c->getMock();
        $second = $c->getMock();
        $third = $c->getMock();

        $mp = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\MessagePart::class)
            ->disableOriginalConstructor();
        $fmp = $mp->getMock();
        $smp = $mp->getMock();
        $tmp = $mp->getMock();

        $first->expects($this->once())->method('parseAll');
        $first->expects($this->any())->method('getPart')->willReturn($fmp);
        $second->expects($this->once())->method('parseAll');
        $second->expects($this->any())->method('getPart')->willReturn($smp);
        $third->expects($this->any())->method('getPart')->willReturn($tmp);

        $this->parser
            ->expects($this->exactly(4))
            ->method('parseNextChild')
            ->willReturn($first, $second, $third, null);

        $this->assertNull($instance->getLastAddedChild());
        $this->assertNull($instance->getAddedChildAt(0));

        $this->assertSame($fmp, $instance->popNextChild());
        $this->assertSame($first, $instance->getAddedChildAt(0));
        $this->assertNull($instance->getAddedChildAt(1));
        $this->assertSame($first, $instance->getLastAddedChild());

        $this->assertSame($smp, $instance->popNextChild());
        $this->assertSame($first, $instance->getAddedChildAt(0));
        $this->assertSame($second, $instance->getAddedChildAt(1));
        $this->assertNull($instance->getAddedChildAt(2));
        $this->assertSame($second, $instance->getLastAddedChild());

        $this->assertSame($tmp, $instance->popNextChild());
        $this->assertSame($first, $instance->getAddedChildAt(0));
        $this->assertSame($second, $instance->getAddedChildAt(1));
        $this->assertSame($third, $instance->getAddedChildAt(2));
        $this->assertNull($instance->getAddedChildAt(3));
        $this->assertSame($third, $instance->getLastAddedChild());
        $this->assertNull($instance->popNextChild());
        $this->assertNull($instance->popNextChild());

        $this->assertSame($first, $instance->getAddedChildAt(0));
        $this->assertSame($second, $instance->getAddedChildAt(1));
        $this->assertSame($third, $instance->getAddedChildAt(2));
    }

    public function testPopNextChildParsesContent() : void
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

    public function testParseAllParsesContentAndChildren() : void
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(true);

        $c = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
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

    public function testPopChildrenAfterParseAll() : void
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(true);

        $c = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
            ->disableOriginalConstructor();
        $first = $c->getMock();
        $second = $c->getMock();
        $third = $c->getMock();

        $mp = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\MessagePart::class)
            ->disableOriginalConstructor();
        $fmp = $mp->getMock();
        $smp = $mp->getMock();
        $tmp = $mp->getMock();

        $first->expects($this->once())->method('parseAll');
        $first->expects($this->any())->method('getPart')->willReturn($fmp);
        $second->expects($this->once())->method('parseAll');
        $second->expects($this->any())->method('getPart')->willReturn($smp);
        $third->expects($this->any())->method('getPart')->willReturn($tmp);

        $this->parser
            ->expects($this->exactly(4))
            ->method('parseNextChild')
            ->willReturnOnConsecutiveCalls($first, $second, $third, null);

        $instance->parseAll();

        $this->assertSame($first, $instance->getAddedChildAt(0));
        $this->assertSame($second, $instance->getAddedChildAt(1));
        $this->assertSame($third, $instance->getAddedChildAt(2));
        $this->assertSame($third, $instance->getLastAddedChild());

        $this->assertSame($fmp, $instance->popNextChild());
        $this->assertSame($smp, $instance->popNextChild());
        $this->assertSame($tmp, $instance->popNextChild());
        $this->assertNull($instance->popNextChild());
        $this->assertNull($instance->popNextChild());

        $this->assertSame($first, $instance->getAddedChildAt(0));
        $this->assertSame($second, $instance->getAddedChildAt(1));
        $this->assertSame($third, $instance->getAddedChildAt(2));
        $this->assertSame($third, $instance->getLastAddedChild());
    }

    public function testGetHeaderContainer() : void
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);
        $this->partBuilder->expects($this->once())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);
        $this->assertSame($this->headerContainer, $instance->getHeaderContainer());
    }

    public function testGetContentType() : void
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

    public function testGetMimeBoundary() : void
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);

        $h = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\ParameterHeader::class)
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

    public function testGetNullMimeBoundary() : void
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

    public function testSetEndBoundaryFound() : void
    {
        $instance = new ParserMimePartProxy($this->partBuilder, $this->parser);

        $h = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\ParameterHeader::class)
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

    public function testSetEndBoundaryFoundWithParentBoundary() : void
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

    public function testSetEof() : void
    {
        $parentPb = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
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

    public function testSetGetLastLineEndingLength() : void
    {
        $parent = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
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
