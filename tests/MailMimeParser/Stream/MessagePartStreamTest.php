<?php

namespace ZBateson\MailMimeParser\Stream;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use ZBateson\StreamDecorators\NonClosingStream;

/**
 * MessagePartStreamTest
 *
 * @group MessagePartStream
 * @group Stream
 * @covers ZBateson\MailMimeParser\Stream\MessagePartStream
 * @author Zaahid Bateson
 */
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

    private function newMockUUEncodedPart() : \ZBateson\MailMimeParser\Message\UUEncodedPart
    {
        return $this->getMockBuilder(\ZBateson\MailMimeParser\Message\UUEncodedPart::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testReadMimeMessageWithChildren() : void
    {
        $testContents = '';

        $message = $this->newMockMessage();
        $message->expects($this->once())
            ->method('getContentStream')
            ->willReturn(Psr7\Utils::streamFor('test'));

        $this->mockStreamFactory->expects($this->once())
            ->method('newNonClosingStream')
            ->willReturnCallback(function($stream) {
                return new NonClosingStream($stream);
            });

        $message->expects($this->once())
            ->method('getContentTransferEncoding')
            ->willReturn('quoted-printable');
        $this->mockStreamFactory->expects($this->once())
            ->method('newQuotedPrintableStream')
            ->willReturnCallback(function($stream) {
                return $stream;
            });

        $message->expects($this->once())
            ->method('getCharset')
            ->willReturn('ISO-8859-1');
        $this->mockStreamFactory->expects($this->once())
            ->method('newCharsetStream')
            ->willReturnCallback(function($stream) {
                return $stream;
            });

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

        $ms = new MessagePartStream($this->mockStreamFactory, $message);
        $this->assertEquals($testContents, $ms->getContents());
    }

    public function testReadBase64MimeMessage() : void
    {
        $testContents = '';

        $message = $this->newMockMessage();
        $message->expects($this->once())
            ->method('getContentStream')
            ->willReturn(Psr7\Utils::streamFor('test'));

        $this->mockStreamFactory->expects($this->once())
            ->method('newNonClosingStream')
            ->willReturnCallback(function($stream) {
                return new NonClosingStream($stream);
            });

        $message->expects($this->once())
            ->method('getContentTransferEncoding')
            ->willReturn('base64');
        $this->mockStreamFactory->expects($this->once())
            ->method('newChunkSplitStream')
            ->willReturnCallback(function($stream) {
                return $stream;
            });
        $this->mockStreamFactory->expects($this->once())
            ->method('newBase64Stream')
            ->willReturnCallback(function($stream) {
                return $stream;
            });

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

        $ms = new MessagePartStream($this->mockStreamFactory, $message);
        $this->assertEquals($testContents, $ms->getContents());
    }

    public function testReadUUEncodedNonMimeMessageWithChildren() : void
    {
        $testContents = '';

        $message = $this->newMockMessage();
        $message->expects($this->once())
            ->method('getContentStream')
            ->willReturn(Psr7\Utils::streamFor('test'));

        $this->mockStreamFactory->expects($this->once())
            ->method('newNonClosingStream')
            ->willReturnCallback(function($stream) {
                return new NonClosingStream($stream);
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
            ->method('newUUStream')
            ->willReturnCallback(function($stream) {
                $mock = $this->getMockBuilder(\ZBateson\StreamDecorators\NonClosingStream::class)
                    ->setConstructorArgs([$stream])
                    ->setMethods(['setFilename'])
                    ->getMock();
                $mock->expects($this->once())
                    ->method('setFilename')
                    ->with('la-file');
                return $mock;
            });

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

        $ms = new MessagePartStream($this->mockStreamFactory, $message);
        $this->assertEquals($testContents, $ms->getContents());
    }

    public function testRead7BitMimeMessage() : void
    {
        $testContents = '';

        $message = $this->newMockMessage();
        $message->expects($this->once())
            ->method('getContentStream')
            ->willReturn(Psr7\Utils::streamFor('test'));

        $this->mockStreamFactory->expects($this->once())
            ->method('newNonClosingStream')
            ->willReturnCallback(function($stream) {
                return new NonClosingStream($stream);
            });

        $message->expects($this->once())
            ->method('getContentTransferEncoding')
            ->willReturn('7bit');
        $this->mockStreamFactory->expects($this->never())
            ->method('newChunkSplitStream');
        $this->mockStreamFactory->expects($this->never())
            ->method('newBase64Stream');
        $this->mockStreamFactory->expects($this->never())
            ->method('newUUStream');
        $this->mockStreamFactory->expects($this->never())
            ->method('newQuotedPrintableStream');

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

        $ms = new MessagePartStream($this->mockStreamFactory, $message);
        $this->assertEquals($testContents, $ms->getContents());
    }
}
