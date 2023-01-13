<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

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
        $this->proxy = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['parseAll', 'parseContent'])
            ->getMockForAbstractClass();
        $this->streamFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new ParserPartStreamContainer($this->streamFactory, $this->proxy);
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

        $this->assertNull($this->instance->getContentStream('7bit', '', ''));
        // doesn't call parseContent again
        $this->assertNull($this->instance->getContentStream('7bit', '', ''));
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

        $this->assertSame('Fighting bears', $this->instance->getContentStream('7bit', '', '')->getContents());
        // doesn't call parseContent again
        $this->assertSame('Fighting bears', $this->instance->getContentStream('7bit', '', '')->getContents());
    }

    public function testGetBinaryContentRequestsContentStream() : void
    {
        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($this->proxy)
            ->willReturn(null);

        $this->assertNull($this->instance->getBinaryContentStream('7bit'));
        // doesn't call parseContent again
        $this->assertNull($this->instance->getBinaryContentStream('7bit'));
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

        $this->assertSame('Fighting bears', $this->instance->getBinaryContentStream('7bit')->getContents());
        // doesn't call parseContent again
        $this->assertSame('Fighting bears', $this->instance->getBinaryContentStream('7bit')->getContents());
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

        $this->assertSame($stream, $this->instance->getStream());
        // doesn't call parseAll again
        $this->assertSame($stream, $this->instance->getStream());
    }

    public function testGetStreamAfterUpdate() : void
    {
        $parsedStream = Utils::streamFor('Fighting bOars');
        $stream = Utils::streamFor('Fighting bears');

        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($this->proxy)
            ->willReturn($parsedStream);

        $this->instance->setStream($stream);
        $this->assertSame('Fighting bOars', $this->instance->getStream()->getContents());

        $subject = $this->getMockBuilder('SplSubject')
            ->getMockForAbstractClass();
        $this->instance->update($subject);
        // doesn't call parseAll again, returns $stream
        $this->assertSame($stream, $this->instance->getStream());
    }

    public function testDetachedParsedStream() : void
    {
        $parsedStream = Utils::streamFor('Fighting bOars', ['metadata' => ['mmp-detached-stream' => true]]);
        $instance = new ParserPartStreamContainer($this->streamFactory, $this->proxy);

        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($this->proxy)
            ->willReturn($parsedStream);

        $this->assertSame('Fighting bOars', $instance->getStream()->getContents());
        $this->assertTrue($parsedStream->isReadable());
        unset($instance);
        $this->assertFalse($parsedStream->isReadable());
    }

    public function testAttachedParsedStream() : void
    {
        $parsedStream = Utils::streamFor('Fighting bOars', ['metadata' => ['mmp-detached-stream' => false]]);
        $instance = new ParserPartStreamContainer($this->streamFactory, $this->proxy);

        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($this->proxy)
            ->willReturn($parsedStream);

        $this->assertSame('Fighting bOars', $instance->getStream()->getContents());
        $this->assertTrue($parsedStream->isReadable());
        unset($instance);
        $this->assertTrue($parsedStream->isReadable());
    }
}
