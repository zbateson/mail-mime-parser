<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

/**
 * ParserNonMimeMessageProxyFactoryTest
 *
 * @group ParserNonMimeMessageProxyFactory
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\Proxy\ParserNonMimeMessageProxyFactory
 * @author Zaahid Bateson
 */
class ParserNonMimeMessageProxyFactoryTest extends TestCase
{
    private $instance;

    private $streamFactory;

    private $headerContainerFactory;

    private $partStreamContainerFactory;

    private $partChildrenContainerFactory;

    private $multipartHelper;

    private $privacyHelper;

    private $headerContainer;

    private $partBuilder;

    private $partStreamContainer;

    private $partChildrenContainer;

    private $parser;

    protected function setUp() : void
    {
        $this->streamFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerContainerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partChildrenContainerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->multipartHelper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MultipartHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->privacyHelper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\PrivacyHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partChildrenContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockForAbstractClass('ZBateson\MailMimeParser\Parser\IParser');

        $this->instance = new ParserNonMimeMessageProxyFactory(
            $this->streamFactory,
            $this->headerContainerFactory,
            $this->partStreamContainerFactory,
            $this->partChildrenContainerFactory,
            $this->multipartHelper,
            $this->privacyHelper
        );
    }

    public function testNewInstance()
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
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy'))
            ->willReturn($this->partStreamContainer);
        $this->partChildrenContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy'))
            ->willReturn($this->partChildrenContainer);
        $stream = Utils::streamFor('test');
        $this->streamFactory->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\IMessage'))
            ->willReturn($stream);
        $this->partStreamContainer->expects($this->once())
            ->method('setStream')
            ->with($stream);

        $ob = $this->instance->newInstance($this->partBuilder, $this->parser);
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\Proxy\ParserNonMimeMessageProxy',
            $ob
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\IMessage',
            $ob->getPart()
        );
    }
}
