<?php

namespace ZBateson\MailMimeParser\Parser;

use PHPUnit\Framework\TestCase;

/**
 * ParserManagerServiceTest
 *
 * @group ParserManagerService
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\ParserManagerService
 * @author Zaahid Bateson
 */
class ParserManagerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mimeParser;

    // @phpstan-ignore-next-line
    private $nonMimeParser;

    protected function setUp() : void
    {
        $this->mimeParser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\MimeParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->nonMimeParser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\NonMimeParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConstructorSetsParserManager() : void
    {
        $this->mimeParser->expects($this->once())
            ->method('setParserManager')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Parser\ParserManagerService::class));
        $this->nonMimeParser->expects($this->once())
            ->method('setParserManager')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Parser\ParserManagerService::class));
        $instance = new ParserManagerService($this->mimeParser, $this->nonMimeParser);
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Container\IService::class, $instance);
    }

    public function testSetParsersSetsParserManager() : void
    {
        $instance = new ParserManagerService($this->mimeParser, $this->nonMimeParser);

        $this->mimeParser->expects($this->once())
            ->method('setParserManager')
            ->with($instance);
        $this->nonMimeParser->expects($this->once())
            ->method('setParserManager')
            ->with($instance);

        $instance->setParsers([$this->mimeParser, $this->nonMimeParser]);
    }

    public function testPrependParserSetsParserManager() : void
    {
        $instance = new ParserManagerService($this->mimeParser, $this->nonMimeParser);

        $this->mimeParser->expects($this->once())
            ->method('setParserManager')
            ->with($instance);

        $instance->prependParser($this->mimeParser);
    }

    public function testCreateParserProxyForMessage() : void
    {
        $instance = new ParserManagerService($this->mimeParser, $this->nonMimeParser);

        $partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proxyFactory = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxyFactory::class);

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

    public function testCreateParserProxyForPart() : void
    {
        $instance = new ParserManagerService($this->mimeParser, $this->nonMimeParser);

        $partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proxyFactory = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxyFactory::class);

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
