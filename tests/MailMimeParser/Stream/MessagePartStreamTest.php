<?php

namespace ZBateson\MailMimeParser\Stream;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\StreamDecorators\CharsetStream;
use ZBateson\StreamDecorators\DecoratedCachingStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * MessagePartStreamTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(MessagePartStream::class)]
#[Group('MessagePartStream')]
#[Group('Stream')]
class MessagePartStreamTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mockStreamFactory;

    protected function setUp() : void
    {
        $this->mockStreamFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newMockMessage() : \ZBateson\MailMimeParser\Message
    {
        return $this->getMockBuilder(\ZBateson\MailMimeParser\Message::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newMockMimePart() : \ZBateson\MailMimeParser\Message\MimePart
    {
        return $this->getMockBuilder(\ZBateson\MailMimeParser\Message\MimePart::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testReadMimeMessageWithChildren() : void
    {
        $testContents = '';

        $message = $this->newMockMessage();
        $message->expects($this->once())
            ->method('getContentStream')
            ->willReturn(new MessagePartStreamDecorator($message, Psr7\Utils::streamFor('test')));

        $this->mockStreamFactory->expects($this->once())
            ->method('newDecoratedCachingStream')
            ->willReturnCallback(function($stream, $callable) {
                return new DecoratedCachingStream($stream, $callable);
            });
        $this->mockStreamFactory->expects($this->once())
            ->method('newSeekingStream')
            ->willReturnArgument(0);

        $message->expects($this->once())
            ->method('getContentTransferEncoding')
            ->willReturn('quoted-printable');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('getTransferEncodingDecoratedStream')
            ->willReturnArgument(0);

        $message->expects($this->once())
            ->method('getCharset')
            ->willReturn('ISO-8859-1');
        $this->mockStreamFactory->expects($this->once())
            ->method('newCharsetStream')
            ->willReturnArgument(0);

        $testContents .= 'test';

        $hs = Psr7\Utils::streamFor('hs');
        $this->mockStreamFactory->expects($this->once())
            ->method('newHeaderStream')
            ->with($message)
            ->willReturn($hs);

        $testContents = 'hs' . $testContents;

        $child1 = $this->newMockMimePart();
        $child2 = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('getHeaderParameter')
            ->willReturn('ze-boundary');
        $message->expects($this->once())
            ->method('getChildParts')
            ->willReturn([$child1, $child2]);
        $message->expects($this->once())
            ->method('hasContent')
            ->willReturn(false);

        $testContents .= "--ze-boundary\r\n";

        $message->expects($this->once())
            ->method('getChildCount')
            ->willReturn(2);
        $child1->expects($this->once())
            ->method('getStream')
            ->willReturn(Psr7\Utils::streamFor('c1'));
        $child2->expects($this->once())
            ->method('getStream')
            ->willReturn(Psr7\Utils::streamFor('c2'));

        $testContents .= 'c1';
        $testContents .= "\r\n--ze-boundary\r\n";
        $testContents .= 'c2';
        $testContents .= "\r\n--ze-boundary--\r\n";

        $ms = new MessagePartStream($this->mockStreamFactory, $message, true);
        $this->assertEquals($testContents, $ms->getContents());
    }

    public function testReadBase64MimeMessage() : void
    {
        $testContents = '';

        $message = $this->newMockMessage();
        $message->expects($this->once())
            ->method('getContentStream')
            ->willReturn(new MessagePartStreamDecorator($message, Psr7\Utils::streamFor('test')));

        $this->mockStreamFactory->expects($this->once())
            ->method('newDecoratedCachingStream')
            ->willReturnCallback(function($stream, $callable) {
                return new DecoratedCachingStream($stream, $callable);
            });
        $this->mockStreamFactory->expects($this->once())
            ->method('newSeekingStream')
            ->willReturnCallback(function($stream) {
                return $stream;
            });

        $message->expects($this->once())
            ->method('getContentTransferEncoding')
            ->willReturn('base64');
        $this->mockStreamFactory->expects($this->once())
            ->method('getTransferEncodingDecoratedStream')
            ->willReturnArgument(0);

        $message->expects($this->once())
            ->method('getCharset')
            ->willReturn(null);

        $testContents .= 'test';

        $hs = Psr7\Utils::streamFor('hs');
        $this->mockStreamFactory->expects($this->once())
            ->method('newHeaderStream')
            ->with($message)
            ->willReturn($hs);

        $testContents = 'hs' . $testContents;

        $ms = new MessagePartStream($this->mockStreamFactory, $message, false);
        $this->assertEquals($testContents, $ms->getContents());
    }

    public function testReadUUEncodedNonMimeMessageWithChildren() : void
    {
        $testContents = '';

        $message = $this->newMockMessage();
        $message->expects($this->once())
            ->method('getContentStream')
            ->willReturn(new MessagePartStreamDecorator($message, Psr7\Utils::streamFor('test')));

        $this->mockStreamFactory->expects($this->once())
            ->method('newDecoratedCachingStream')
            ->willReturnCallback(function($stream, $callable) {
                return new DecoratedCachingStream($stream, $callable);
            });
        $this->mockStreamFactory->expects($this->once())
            ->method('newSeekingStream')
            ->willReturnCallback(function($stream) {
                return $stream;
            });

        $message->expects($this->once())
            ->method('getContentTransferEncoding')
            ->willReturn('x-uuencode');
        $message->expects($this->once())
            ->method('getFilename')
            ->willReturn('la-file');
        $message->expects($this->once())
            ->method('getFilename')
            ->willReturn('la-file');
        $this->mockStreamFactory->expects($this->once())
            ->method('getTransferEncodingDecoratedStream')
            ->with($this->anything(), 'x-uuencode', 'la-file')
            ->willReturnArgument(0);

        $message->expects($this->once())
            ->method('getCharset')
            ->willReturn(null);

        $testContents .= 'test';

        $hs = Psr7\Utils::streamFor('hs');
        $this->mockStreamFactory->expects($this->once())
            ->method('newHeaderStream')
            ->with($message)
            ->willReturn($hs);

        $testContents = 'hs' . $testContents;

        $child1 = $this->newMockMimePart();
        $child2 = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('getHeaderParameter')
            ->willReturn(null);
        $message->expects($this->once())
            ->method('getChildParts')
            ->willReturn([$child1, $child2]);

        $message->expects($this->once())
            ->method('getChildCount')
            ->willReturn(2);
        $child1->expects($this->once())
            ->method('getStream')
            ->willReturn(Psr7\Utils::streamFor('c1'));
        $child2->expects($this->once())
            ->method('getStream')
            ->willReturn(Psr7\Utils::streamFor('c2'));

        $testContents .= 'c1';
        $testContents .= 'c2';

        $ms = new MessagePartStream($this->mockStreamFactory, $message, false);
        $this->assertEquals($testContents, $ms->getContents());
    }

    public function testRead7BitMimeMessage() : void
    {
        $testContents = '';

        $message = $this->newMockMessage();
        $message->expects($this->once())
            ->method('getContentStream')
            ->willReturn(new MessagePartStreamDecorator($message, Psr7\Utils::streamFor('test')));

        $this->mockStreamFactory->expects($this->once())
            ->method('newDecoratedCachingStream')
            ->willReturnCallback(function($stream, $callable) {
                return new DecoratedCachingStream($stream, $callable);
            });
        $this->mockStreamFactory->expects($this->once())
            ->method('newSeekingStream')
            ->willReturnCallback(function($stream) {
                return $stream;
            });

        $message->expects($this->once())
            ->method('getContentTransferEncoding')
            ->willReturn('7bit');
        $this->mockStreamFactory->expects($this->once())
            ->method('getTransferEncodingDecoratedStream')
            ->with($this->anything(), '7bit')
            ->willReturnArgument(0);

        $message->expects($this->once())
            ->method('getCharset')
            ->willReturn(null);

        $testContents .= 'test';

        $hs = Psr7\Utils::streamFor('hs');
        $this->mockStreamFactory->expects($this->once())
            ->method('newHeaderStream')
            ->with($message)
            ->willReturn($hs);

        $testContents = 'hs' . $testContents;

        $ms = new MessagePartStream($this->mockStreamFactory, $message, false);
        $this->assertEquals($testContents, $ms->getContents());
    }

    private function setupForInvalidCharsetTests() : IMessage
    {
        $testContents = '';

        $message = $this->newMockMessage();
        $message->expects($this->once())
            ->method('getContentStream')
            ->willReturn(new MessagePartStreamDecorator($message, Psr7\Utils::streamFor('test')));

        $this->mockStreamFactory->expects($this->once())
            ->method('getTransferEncodingDecoratedStream')
            ->willReturnArgument(0);
        $this->mockStreamFactory->expects($this->once())
            ->method('newDecoratedCachingStream')
            ->willReturnCallback(function($stream, $callable) {
                return new DecoratedCachingStream($stream, $callable);
            });
        $this->mockStreamFactory->expects($this->once())
            ->method('newSeekingStream')
            ->willReturnCallback(function($stream) {
                return $stream;
            });

        $message->expects($this->once())
            ->method('getCharset')
            ->willReturn('asdf');
        $this->mockStreamFactory->expects($this->once())
            ->method('newCharsetStream')
            ->willReturnCallback(function($stream) {
                return new CharsetStream($stream, 'invalid-charset');
            });

        $testContents .= 'test';

        $hs = Psr7\Utils::streamFor('hs');
        $this->mockStreamFactory->expects($this->once())
            ->method('newHeaderStream')
            ->with($message)
            ->willReturn($hs);

        $testContents = 'hs' . $testContents;
        return $message;
    }

    public function testReadMessageWithInvalidCharsetThrowsException() : void
    {
        $message = $this->setupForInvalidCharsetTests();
        $this->expectException(MessagePartStreamReadException::class);
        $ms = new MessagePartStream($this->mockStreamFactory, $message, true);
        $ms->getContents();
    }

    public function testReadMessageWithInvalidCharsetDoesntThrowExceptionWithOption() : void
    {
        $message = $this->setupForInvalidCharsetTests();
        $ms = new MessagePartStream($this->mockStreamFactory, $message, false);
        $this->assertEquals('hstest', $ms->getContents());
    }
}
