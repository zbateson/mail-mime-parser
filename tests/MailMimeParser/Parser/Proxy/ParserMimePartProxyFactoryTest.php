<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Stream\MessagePartStreamDecorator;

/**
 * ParserMimePartProxyFactoryTest
 *
 * @group ParserMimePartProxyFactory
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxyFactory
 * @author Zaahid Bateson
 */
class ParserMimePartProxyFactoryTest extends TestCase
{
  // @phpstan-ignore-next-line
    private $instance;

    // @phpstan-ignore-next-line
    private $streamFactory;

    // @phpstan-ignore-next-line
    private $headerContainerFactory;

    // @phpstan-ignore-next-line
    private $partStreamContainerFactory;

    // @phpstan-ignore-next-line
    private $partChildrenContainerFactory;

    // @phpstan-ignore-next-line
    private $headerContainer;

    // @phpstan-ignore-next-line
    private $partBuilder;

    // @phpstan-ignore-next-line
    private $partStreamContainer;

    // @phpstan-ignore-next-line
    private $partChildrenContainer;

    // @phpstan-ignore-next-line
    private $parser;

    // @phpstan-ignore-next-line
    private $parent;

    protected function setUp() : void
    {
        $this->streamFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerContainerFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainerFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partChildrenContainerFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->headerContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partChildrenContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Parser\IParserService::class);

        $this->parent = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new ParserMimePartProxyFactory(
            $this->streamFactory,
            $this->headerContainerFactory,
            $this->partStreamContainerFactory,
            $this->partChildrenContainerFactory
        );
    }

    public function testNewInstance() : void
    {
        $this->headerContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->headerContainer)
            ->willReturn($this->headerContainer);
        $this->partBuilder->expects($this->once())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);
        $this->partStreamContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class))
            ->willReturn($this->partStreamContainer);
        $this->partChildrenContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class))
            ->willReturn($this->partChildrenContainer);
        $stream = $this->getMockBuilder(MessagePartStreamDecorator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->streamFactory->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Message\IMimePart::class))
            ->willReturn($stream);
        $this->partStreamContainer->expects($this->once())
            ->method('setStream')
            ->with($stream);
        $this->partBuilder->expects($this->once())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->parent->expects($this->once())
            ->method('getPart')
            ->willReturn($this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMimePart::class));

        $ob = $this->instance->newInstance($this->partBuilder, $this->parser);
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class,
            $ob
        );
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Message\IMimePart::class,
            $ob->getPart()
        );
    }
}
