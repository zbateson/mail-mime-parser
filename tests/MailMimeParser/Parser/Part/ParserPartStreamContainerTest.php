<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Log\NullLogger;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use ZBateson\MailMimeParser\Stream\MessagePartStreamDecorator;

/**
 * ParserPartStreamContainerTest
 *
 * @group Parser
 * @group ParserPartStreamContainer
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer
 * @author Zaahid Bateson
 */
class ParserPartStreamContainerTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $instance;

    // @phpstan-ignore-next-line
    private $streamFactory;

    // @phpstan-ignore-next-line
    private $proxy;

    protected function setUp() : void
    {
        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $this->proxy = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['parseAll', 'parseContent', 'getPart'])
            ->getMockForAbstractClass();
        $this->streamFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->proxy->expects($this->any())
            ->method('getPart')
            ->willReturn($streamPartMock);
        $this->instance = new ParserPartStreamContainer(\mmpGetTestLogger(), $this->streamFactory, new MbWrapper(), false, $this->proxy);
    }

    public function testHasContentRequestsContentStream() : void
    {
        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($this->proxy)
            ->willReturn(null);

        $this->assertFalse($this->instance->hasContent());
        // doesn't call parseContent again
        $this->assertFalse($this->instance->hasContent());
    }

    public function testHasContentRequestsContentStreamReturnsTrue() : void
    {
        $stream = Utils::streamFor('Fighting bears');

        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($this->proxy)
            ->willReturn($stream);

        $this->assertTrue($this->instance->hasContent());
        // doesn't call parseContent again
        $this->assertTrue($this->instance->hasContent());
    }

    public function testGetContentRequestsContentStream() : void
    {
        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($this->proxy)
            ->willReturn(null);

        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $this->assertNull($this->instance->getContentStream($streamPartMock, '7bit', '', ''));
        // doesn't call parseContent again
        $this->assertNull($this->instance->getContentStream($streamPartMock, '7bit', '', ''));
    }

    public function testGetContentRequestsContentStreamReturnsStream() : void
    {
        $stream = Utils::streamFor('Fighting bears');

        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($this->proxy)
            ->willReturn($stream);
        $this->streamFactory->expects($this->once())
            ->method('getTransferEncodingDecoratedStream')
            ->with($this->anything(), '7bit')
            ->willReturn($stream);
        $this->streamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });

        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $this->assertSame('Fighting bears', $this->instance->getContentStream($streamPartMock, '7bit', '', '')->getContents());
        // doesn't call parseContent again
        $this->assertSame('Fighting bears', $this->instance->getContentStream($streamPartMock, '7bit', '', '')->getContents());
    }

    public function testGetBinaryContentRequestsContentStream() : void
    {
        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($this->proxy)
            ->willReturn(null);

        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $this->assertNull($this->instance->getBinaryContentStream($streamPartMock, '7bit'));
        // doesn't call parseContent again
        $this->assertNull($this->instance->getBinaryContentStream($streamPartMock, '7bit'));
    }

    public function testGetBinaryContentRequestsContentStreamReturnsStream() : void
    {
        $stream = Utils::streamFor('Fighting bears');

        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($this->proxy)
            ->willReturn($stream);

        $this->streamFactory->expects($this->once())
            ->method('getTransferEncodingDecoratedStream')
            ->with($this->anything(), '7bit')
            ->willReturn($stream);
        $this->streamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });

        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $this->assertSame('Fighting bears', $this->instance->getBinaryContentStream($streamPartMock, '7bit')->getContents());
        // doesn't call parseContent again
        $this->assertSame('Fighting bears', $this->instance->getBinaryContentStream($streamPartMock, '7bit')->getContents());
    }

    public function testSetContentStreamRequestsContentStream() : void
    {
        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($this->proxy)
            ->willReturn(null);

        $this->instance->setContentStream(Utils::streamFor(''));
        // doesn't call parseContent again
        $this->instance->setContentStream(Utils::streamFor(''));
    }

    public function testGetStreamParsesPart() : void
    {
        $stream = Utils::streamFor('Fighting bears');

        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($this->proxy)
            ->willReturn($stream);
        $this->streamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });

        $this->assertEquals('Fighting bears', $this->instance->getStream()->getContents());
        // doesn't call parseAll again
        $this->assertSame('Fighting bears', $this->instance->getStream()->getContents());
    }

    public function testGetStreamAfterUpdate() : void
    {
        $parsedStream = Utils::streamFor('Fighting bOars');
        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $stream = new MessagePartStreamDecorator($streamPartMock, Utils::streamFor('Fighting bears'));

        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($this->proxy)
            ->willReturn($parsedStream);
        $this->streamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });

        $this->instance->setStream($stream);
        $this->assertSame('Fighting bOars', $this->instance->getStream()->getContents());

        $subject = $this->getMockBuilder('SplSubject')
            ->getMockForAbstractClass();
        $this->instance->update($subject);
        // doesn't call parseAll again, returns $stream
        $this->assertSame('Fighting bears', $this->instance->getStream()->getContents());
    }

    public function testDetachedParsedStream() : void
    {
        $parsedStream = Utils::streamFor('Fighting bOars', ['metadata' => ['mmp-detached-stream' => true]]);

        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($this->proxy)
            ->willReturn($parsedStream);
        $this->streamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });

        $this->assertSame('Fighting bOars', $this->instance->getStream()->getContents());
        $this->assertTrue($parsedStream->isReadable());
        unset($this->instance);
        $this->assertFalse($parsedStream->isReadable());
    }

    public function testAttachedParsedStream() : void
    {
        $parsedStream = Utils::streamFor('Fighting bOars', ['metadata' => ['mmp-detached-stream' => false]]);
        
        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($this->proxy)
            ->willReturn($parsedStream);
        $this->streamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });

        $this->assertSame('Fighting bOars', $this->instance->getStream()->getContents());
        $this->assertTrue($parsedStream->isReadable());
        unset($this->instance);
        $this->assertTrue($parsedStream->isReadable());
    }
}
