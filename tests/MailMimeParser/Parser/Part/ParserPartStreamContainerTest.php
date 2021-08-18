<?php
namespace ZBateson\MailMimeParser\Parser\Part;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7\Utils;

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
    private $instance;
    private $streamFactory;
    private $proxy;

    protected function legacySetUp()
    {
        $this->proxy = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy')
            ->disableOriginalConstructor()
            ->setMethods([ 'parseAll', 'parseContent' ])
            ->getMockForAbstractClass();
        $this->streamFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new ParserPartStreamContainer($this->streamFactory, $this->proxy);
    }

    public function testHasContentRequestsContentStream()
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

    public function testHasContentRequestsContentStreamReturnsTrue()
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

    public function testGetContentRequestsContentStream()
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

    public function testGetContentRequestsContentStreamReturnsStream()
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

    public function testGetBinaryContentRequestsContentStream()
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

    public function testGetBinaryContentRequestsContentStreamReturnsStream()
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

    public function testSetContentStreamRequestsContentStream()
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

    public function testGetStreamParsesPart()
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

    public function testGetStreamAfterUpdate()
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

    public function testDetachedParsedStream()
    {
        $parsedStream = Utils::streamFor('Fighting bOars', [ 'metadata' => [ 'mmp-detached-stream' => true ] ]);
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

    public function testAttachedParsedStream()
    {
        $parsedStream = Utils::streamFor('Fighting bOars', [ 'metadata' => [ 'mmp-detached-stream' => false ] ]);
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
