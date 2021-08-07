<?php
namespace ZBateson\MailMimeParser\Parser\Proxy;

use LegacyPHPUnit\TestCase;

/**
 * ParserMimePartProxyTest
 *
 * @group Parser
 * @group ParserMimePartProxy
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserMimePartProxy
 * @author Zaahid Bateson
 */
class ParserMimePartProxyTest extends TestCase
{
    private $headerContainer;
    private $partBuilder;
    private $childParser;
    private $parentParser;
    private $parent;

    protected function legacySetUp()
    {
        $hc = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor();
        $this->headerContainer = $hc->getMock();
        $pbm = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor();
        $this->partBuilder = $pbm->getMock();
        $this->childParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\IParser')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->parentParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\IParser')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->parent = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy')
            ->setConstructorArgs([
                $hc->getMock(),
                $pbm->getMock(),
                $this->parentParser,
                null
            ])->getMock();
    }

    public function testPopNextChild()
    {
        $instance = new ParserMimePartProxy($this->headerContainer, $this->partBuilder, $this->childParser, $this->parent);
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

        $this->childParser
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
        $instance = new ParserMimePartProxy($this->headerContainer, $this->partBuilder, $this->childParser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->childParser
            ->expects($this->once())
            ->method('parseContent')
            ->with($instance);
        $this->childParser
            ->expects($this->once())
            ->method('parseNextChild')
            ->willReturn(null);

        $this->assertNull($instance->popNextChild());
        $this->assertNull($instance->popNextChild());
    }

    public function testParseAllParsesContentAndChildren()
    {
        $instance = new ParserMimePartProxy($this->headerContainer, $this->partBuilder, $this->childParser, $this->parent);
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
        
        $this->childParser
            ->expects($this->exactly(4))
            ->method('parseNextChild')
            ->willReturnOnConsecutiveCalls($first, $second, $third, null);

        $instance->parseAll();
    }

    public function testPopChildrenAfterParseAll()
    {
        $instance = new ParserMimePartProxy($this->headerContainer, $this->partBuilder, $this->childParser, $this->parent);
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

        $this->childParser
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
        $instance = new ParserMimePartProxy($this->headerContainer, $this->partBuilder, $this->childParser);
        $this->assertSame($this->headerContainer, $instance->getHeaderContainer());
    }

    public function testGetContentType()
    {
        $instance = new ParserMimePartProxy($this->headerContainer, $this->partBuilder, $this->childParser);
        $this->headerContainer->expects($this->any())
            ->method('get')
            ->with($this->equalToIgnoringCase('Content-Type'))
            ->willReturn('Fruity');
        $this->assertSame('Fruity', $instance->getContentType());
    }

    public function testGetMimeBoundary()
    {
        $instance = new ParserMimePartProxy($this->headerContainer, $this->partBuilder, $this->childParser);

        $h = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerContainer
            ->expects($this->any())
            ->method('get')
            ->with($this->equalToIgnoringCase('Content-Type'))
            ->willReturn($h);

        $h->expects($this->any())
            ->method('getValueFor')
            ->with($this->equalToIgnoringCase('boundary'))
            ->willReturn('Personal Space');
        $this->assertSame('Personal Space', $instance->getMimeBoundary());
        $this->assertSame('Personal Space', $instance->getMimeBoundary());
    }

    public function testGetNullMimeBoundary()
    {
        $instance = new ParserMimePartProxy($this->headerContainer, $this->partBuilder, $this->childParser);

        $this->headerContainer
            ->expects($this->any())
            ->method('get')
            ->with($this->equalToIgnoringCase('Content-Type'))
            ->willReturn(null);
        $this->assertNull($instance->getMimeBoundary());
        $this->assertNull($instance->getMimeBoundary());
    }

    public function testSetEndBoundaryFound()
    {
        $instance = new ParserMimePartProxy($this->headerContainer, $this->partBuilder, $this->childParser);

        $h = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerContainer
            ->expects($this->any())
            ->method('get')
            ->with($this->equalToIgnoringCase('Content-Type'))
            ->willReturn($h);
        $h->expects($this->any())
            ->method('getValueFor')
            ->with($this->equalToIgnoringCase('boundary'))
            ->willReturn('Personal Space');

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
        $instance = new ParserMimePartProxy($this->headerContainer, $this->partBuilder, $this->childParser, $this->parent);

        $this->headerContainer
            ->expects($this->any())
            ->method('get')
            ->with($this->equalToIgnoringCase('Content-Type'))
            ->willReturn(null);

        $this->parent->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->assertFalse($instance->isEndBoundaryFound());
        $this->assertFalse($instance->isParentBoundaryFound());
        $this->assertFalse($instance->setEndBoundaryFound('Not in your personal space'));
        $this->assertTrue($instance->setEndBoundaryFound('--Personal Space'));
        $this->assertFalse($instance->isEndBoundaryFound());
        $this->assertTrue($instance->isParentBoundaryFound());
    }

    public function testSetEof()
    {
        $parent = new ParserMimePartProxy(
            $this->headerContainer,
            $this->partBuilder,
            $this->childParser
        );
        $instance = new ParserMimePartProxy(
            $this->headerContainer,
            $this->partBuilder,
            $this->childParser,
            $parent
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
}
