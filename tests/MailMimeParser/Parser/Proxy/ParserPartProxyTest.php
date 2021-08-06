<?php
namespace ZBateson\MailMimeParser\Parser\Proxy;

use LegacyPHPUnit\TestCase;

/**
 * ParserPartProxyTest
 *
 * @group Parser
 * @group ParserPartProxy
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserPartProxy
 * @author Zaahid Bateson
 */
class ParserPartProxyTest extends TestCase
{
    private $partBuilder;
    private $childParser;
    private $parentParser;
    private $parent;

    protected function legacySetUp()
    {
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
                $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')->disableOriginalConstructor()->getMock(),
                $pbm->getMock(),
                $this->parentParser,
                null
            ])->getMock();
    }

    public function testSetGetPart()
    {
        $part = $this->getMockBuilder('ZBateson\MailMimeParser\Message\IMessagePart')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $instance = new ParserPartProxy($this->partBuilder);
        $instance->setPart($part);
        $this->assertSame($part, $instance->getPart());
    }

    public function testParseContent()
    {
        $instance = new ParserPartProxy($this->partBuilder, $this->childParser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->childParser
            ->expects($this->once())
            ->method('parseContent')
            ->with($instance);

        $instance->parseContent();
        $instance->parseContent();
    }

    public function testParseContentWithParentParser()
    {
        $instance = new ParserPartProxy($this->partBuilder, $this->childParser, $this->parent);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->childParser
            ->expects($this->never())
            ->method('parseContent');
        $this->parentParser
            ->expects($this->once())
            ->method('parseContent')
            ->with($instance);

        $instance->parseContent();
        $instance->parseContent();
    }

    public function testParseAllParsesContent()
    {
        $instance = new ParserPartProxy($this->partBuilder, $this->childParser);
        $this->partBuilder
            ->expects($this->any())
            ->method('isContentParsed')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->childParser
            ->expects($this->once())
            ->method('parseContent');
        $instance->parseAll();
        $instance->parseAll();
    }

    public function testGetPartBuilder()
    {
        $instance = new ParserPartProxy($this->partBuilder, $this->childParser);
        $this->assertSame($this->partBuilder, $instance->getPartBuilder());
    }
}
