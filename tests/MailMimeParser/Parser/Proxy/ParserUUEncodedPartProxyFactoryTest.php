<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

/**
 * ParserUUEncodedPartProxyFactoryTest
 *
 * @group ParserUUEncodedPartProxyFactory
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\Proxy\ParserUUEncodedPartProxyFactory
 * @author Zaahid Bateson
 */
class ParserUUEncodedPartProxyFactoryTest extends TestCase
{
    private $instance;

    private $streamFactory;

    private $headerContainer;

    private $partStreamContainerFactory;

    private $partBuilder;

    private $partStreamContainer;

    private $parser;

    private $parent;

    protected function setUp() : void
    {
        $this->streamFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainerFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->headerContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Part\UUEncodedPartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Parser\IParser::class);

        $this->parent = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new ParserUUEncodedPartProxyFactory(
            $this->streamFactory,
            $this->partStreamContainerFactory
        );
    }

    public function testNewInstance()
    {
        $this->partStreamContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy::class))
            ->willReturn($this->partStreamContainer);
        $stream = Utils::streamFor('test');
        $this->streamFactory->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Message\IUUEncodedPart::class))
            ->willReturn($stream);
        $this->partStreamContainer->expects($this->once())
            ->method('setStream')
            ->with($stream);
        $this->parent->expects($this->atLeastOnce())
            ->method('getPart')
            ->willReturn($this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMimePart::class));

        $this->partBuilder
            ->expects($this->atLeastOnce())
            ->method('getParent')
            ->willReturn($this->parent);
        $this->partBuilder
            ->expects($this->atLeastOnce())
            ->method('getHeaderContainer')
            ->willReturn($this->headerContainer);
        $this->headerContainer
            ->expects($this->atLeastOnce())
            ->method('getUnixFileMode')
            ->willReturn(0644);
        $this->headerContainer
            ->expects($this->atLeastOnce())
            ->method('getFilename')
            ->willReturn('test-file.ext');

        $ob = $this->instance->newInstance($this->partBuilder, $this->parser);
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy::class,
            $ob
        );
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Message\IUUEncodedPart::class,
            $ob->getPart()
        );
        $this->assertSame(0644, $ob->getPart()->getUnixFileMode());
        $this->assertSame('test-file.ext', $ob->getPart()->getFilename());
    }
}
