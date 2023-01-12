<?php

namespace ZBateson\MailMimeParser\Parser;

use PHPUnit\Framework\TestCase;

/**
 * ParserManagerTest
 *
 * @group ParserManager
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\ParserManager
 * @author Zaahid Bateson
 */
class ParserManagerTest extends TestCase
{
    private $mimeParser;

    private $nonMimeParser;

    protected function setUp() : void
    {
        $this->mimeParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\MimeParser')
            ->disableOriginalConstructor()
            ->getMock();
        $this->nonMimeParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\NonMimeParser')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConstructorSetsParserManager()
    {
        $this->mimeParser->expects($this->once())
            ->method('setParserManager')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\ParserManager'));
        $this->nonMimeParser->expects($this->once())
            ->method('setParserManager')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\ParserManager'));
        $instance = new ParserManager($this->mimeParser, $this->nonMimeParser);
    }

    public function testSetParsersSetsParserManager()
    {
        $instance = new ParserManager($this->mimeParser, $this->nonMimeParser);

        $this->mimeParser->expects($this->once())
            ->method('setParserManager')
            ->with($instance);
        $this->nonMimeParser->expects($this->once())
            ->method('setParserManager')
            ->with($instance);

        $instance->setParsers([$this->mimeParser, $this->nonMimeParser]);
    }

    public function testPrependParserSetsParserManager()
    {
        $instance = new ParserManager($this->mimeParser, $this->nonMimeParser);

        $this->mimeParser->expects($this->once())
            ->method('setParserManager')
            ->with($instance);

        $instance->prependParser($this->mimeParser);
    }

    public function testCreateParserProxyForMessage()
    {
        $instance = new ParserManager($this->mimeParser, $this->nonMimeParser);

        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $proxyFactory = $this->getMockForAbstractClass('ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxyFactory');

        $this->mimeParser->expects($this->once())
            ->method('canParse')
            ->with($partBuilder)
            ->willReturn(false);
        $this->mimeParser->expects($this->never())
            ->method('getParserMessageProxyFactory');
        $this->nonMimeParser->expects($this->once())
            ->method('canParse')
            ->with($partBuilder)
            ->willReturn(true);
        $this->nonMimeParser->expects($this->once())
            ->method('getParserMessageProxyFactory')
            ->willReturn($proxyFactory);
        $proxyFactory->expects($this->once())
            ->method('newInstance')
            ->with($partBuilder, $this->nonMimeParser)
            ->willReturn('t000st');

        $this->assertSame('t000st', $instance->createParserProxyFor($partBuilder));
    }

    public function testCreateParserProxyForPart()
    {
        $instance = new ParserManager($this->mimeParser, $this->nonMimeParser);

        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $proxyFactory = $this->getMockForAbstractClass('ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxyFactory');

        $this->mimeParser->expects($this->once())
            ->method('canParse')
            ->with($partBuilder)
            ->willReturn(true);
        $partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn('non null');
        $this->mimeParser->expects($this->never())
            ->method('getParserMessageProxyFactory');
        $this->mimeParser->expects($this->once())
            ->method('getParserPartProxyFactory')
            ->willReturn($proxyFactory);
        $this->nonMimeParser->expects($this->never())
            ->method('canParse');
        $this->nonMimeParser->expects($this->never())
            ->method('getParserPartProxyFactory');
        $proxyFactory->expects($this->once())
            ->method('newInstance')
            ->with($partBuilder, $this->mimeParser)
            ->willReturn('t000st');

        $this->assertSame('t000st', $instance->createParserProxyFor($partBuilder));
    }
}
