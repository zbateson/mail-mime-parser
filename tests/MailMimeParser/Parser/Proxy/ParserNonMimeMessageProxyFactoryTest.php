<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Stream\MessagePartStreamDecorator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * ParserNonMimeMessageProxyFactoryTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(ParserNonMimeMessageProxyFactory::class)]
#[Group('ParserNonMimeMessageProxyFactory')]
#[Group('Parser')]
class ParserNonMimeMessageProxyFactoryTest extends TestCase
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
    private $multipartHelper;

    // @phpstan-ignore-next-line
    private $privacyHelper;

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
        $this->multipartHelper = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Helper\MultipartHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->privacyHelper = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Helper\PrivacyHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partChildrenContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->createMock(\ZBateson\MailMimeParser\Parser\IParserService::class);

        $this->instance = new ParserNonMimeMessageProxyFactory(
            \mmpGetTestLogger(),
            $this->streamFactory,
            $this->headerContainerFactory,
            $this->partStreamContainerFactory,
            $this->partChildrenContainerFactory,
            $this->multipartHelper,
            $this->privacyHelper
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
            ->with($this->isInstanceOf(ParserMimePartProxy::class))
            ->willReturn($this->partStreamContainer);
        $this->partChildrenContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf(ParserMimePartProxy::class))
            ->willReturn($this->partChildrenContainer);
        $stream = $this->getMockBuilder(MessagePartStreamDecorator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->streamFactory->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\IMessage::class))
            ->willReturn($stream);
        $this->partStreamContainer->expects($this->once())
            ->method('setStream')
            ->with($stream);

        $ob = $this->instance->newInstance($this->partBuilder, $this->parser);
        $this->assertInstanceOf(
            ParserNonMimeMessageProxy::class,
            $ob
        );
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\IMessage::class,
            $ob->getPart()
        );
    }
}
