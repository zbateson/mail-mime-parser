<?php

namespace ZBateson\MailMimeParser\Message;

use Psr\Log\NullLogger;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use ZBateson\MailMimeParser\Stream\MessagePartStreamDecorator;

/**
 * PartStreamFilterManagerTest
 *
 * @group PartStreamContainer
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\PartStreamContainer
 * @author Zaahid Bateson
 */
class PartStreamContainerTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $instance = null;

    // @phpstan-ignore-next-line
    private $mockStreamFactory = null;

    protected function setUp() : void
    {
        $this->mockStreamFactory = $this
            ->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->setConstructorArgs([true])
            ->getMock();
        $this->instance = new PartStreamContainer(new NullLogger(), $this->mockStreamFactory, new MbWrapper(), false);
    }

    public function testSetAndGetStream() : void
    {
        $stream = $this->getMockBuilder(MessagePartStreamDecorator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance->setStream($stream);
        $stream->expects($this->once())->method('rewind');
        $this->assertSame($stream, $this->instance->getStream());
    }

    public function testSetContentStreamAndHasContent() : void
    {
        $stream = $this->getMockBuilder(MessagePartStreamDecorator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $this->assertFalse($this->instance->hasContent());
        $this->assertNull($this->instance->getContentStream($streamPartMock, '', '', ''));
        $this->assertNull($this->instance->getBinaryContentStream($streamPartMock, ''));
        $this->instance->setContentStream($stream);
        $this->assertTrue($this->instance->hasContent());
    }

    public function testGetBinaryStream() : void
    {
        $stream = Psr7\Utils::streamFor('test');
        $stream2 = Psr7\Utils::streamFor('test2');
        $stream3 = Psr7\Utils::streamFor('test3');
        $this->mockStreamFactory->expects($this->exactly(3))
            ->method('getTransferEncodingDecoratedStream')
            ->withConsecutive(
                [$stream, 'x-uuencode', null],
                [$stream, 'quoted-printable', null],
                [$stream, 'x-uuencode', null]
            )
            ->willReturnOnConsecutiveCalls($stream2, $stream, $stream3);

        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $this->mockStreamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });

        $this->instance->setContentStream($stream);

        $manager = $this->instance;
        $this->assertEquals('test2', $manager->getBinaryContentStream($streamPartMock, 'x-uuencode')->getContents());
        $this->assertEquals('test2', $manager->getBinaryContentStream($streamPartMock, 'x-uuencode')->getContents());
        $this->assertEquals('test2', $manager->getBinaryContentStream($streamPartMock, 'x-uuencode')->getContents());

        $this->assertEquals('test', $manager->getBinaryContentStream($streamPartMock, 'quoted-printable')->getContents());
        $this->assertEquals('test', $manager->getBinaryContentStream($streamPartMock, 'quoted-printable')->getContents());

        $this->assertEquals('test3', $manager->getBinaryContentStream($streamPartMock, 'x-uuencode')->getContents());
    }

    public function testGetContentStreamWithQuotedPrintableDecoderTransferEncoding() : void
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('getTransferEncodingDecoratedStream')
            ->with($stream, 'quoted-printable')
            ->willReturn($stream);
        $this->mockStreamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });
        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);

        $this->assertNull($this->instance->getContentStream($streamPartMock, 'quoted-printable', null, null));
        $this->instance->setContentStream($stream);
        $managerStream = $this->instance->getContentStream($streamPartMock, 'quoted-printable', null, null);
        $this->assertEquals('test', $managerStream->getContents());
    }

    public function testGetContentStreamWithBase64DecoderTransferEncoding() : void
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('getTransferEncodingDecoratedStream')
            ->with($stream, 'base64')
            ->willReturn($stream);
        $this->mockStreamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });
        $this->instance->setContentStream($stream);
        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $managerStream = $this->instance->getContentStream($streamPartMock, 'base64', null, null);
        $this->assertEquals('test', $managerStream->getContents());
    }

    public function testGetContentStreamWithUUDecoderTransferEncoding() : void
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('getTransferEncodingDecoratedStream')
            ->with($stream, 'x-uuencode')
            ->willReturn($stream);
        $this->mockStreamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });
        $this->instance->setContentStream($stream);
        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $managerStream = $this->instance->getContentStream($streamPartMock, 'x-uuencode', null, null);
        $this->assertEquals('test', $managerStream->getContents());
    }

    public function testGetContentStreamWithCharsetEncoding() : void
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newCharsetStream')
            ->with($this->anything(), 'US-ASCII', 'UTF-8')
            ->willReturn($stream);
        $this->mockStreamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });
        $this->instance->setContentStream($stream);
        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $managerStream = $this->instance->getContentStream($streamPartMock, null, 'US-ASCII', 'UTF-8');
        $this->assertEquals('test', $managerStream->getContents());
    }

    public function testGetContentStreamWithReAttachedTransferEncodingDecoder() : void
    {
        $stream = Psr7\Utils::streamFor('test');
        $stream2 = Psr7\Utils::streamFor('test2');
        $stream3 = Psr7\Utils::streamFor('test3');
        $this->mockStreamFactory->expects($this->exactly(3))
            ->method('getTransferEncodingDecoratedStream')
            ->withConsecutive(
                [$stream, 'x-uuencode', null],
                [$stream, 'quoted-printable', null],
                [$stream, 'x-uuencode', null]
            )
            ->willReturnOnConsecutiveCalls($stream2, $stream, $stream3);
        $this->mockStreamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });
        
        $this->instance->setContentStream($stream);

        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $manager = $this->instance;
        $this->assertEquals('test2', $manager->getContentStream($streamPartMock, 'x-uuencode', null, null)->getContents());
        $this->assertEquals('test2', $manager->getContentStream($streamPartMock, 'x-uuencode', null, null)->getContents());
        $this->assertEquals('test2', $manager->getContentStream($streamPartMock, 'x-uuencode', null, null)->getContents());

        $this->assertEquals('test', $manager->getContentStream($streamPartMock, 'quoted-printable', null, null)->getContents());
        $this->assertEquals('test', $manager->getContentStream($streamPartMock, 'quoted-printable', null, null)->getContents());

        $this->assertEquals('test3', $manager->getContentStream($streamPartMock, 'x-uuencode', null, null)->getContents());
    }

    public function testGetContentStreamWithReAttachedCharsetConversionDecoder() : void
    {
        $stream = Psr7\Utils::streamFor('test');
        $stream2 = Psr7\Utils::streamFor('test2');
        $stream3 = Psr7\Utils::streamFor('test3');
        $stream4 = Psr7\Utils::streamFor('test4');
        $this->mockStreamFactory->expects($this->exactly(4))
            ->method('newCharsetStream')
            ->withConsecutive(
                [$this->anything(), 'US-ASCII', 'UTF-8'],
                [$this->anything(), 'US-ASCII', 'WINDOWS-1252'],
                [$this->anything(), 'ISO-8859-1', 'WINDOWS-1252'],
                [$this->anything(), 'WINDOWS-1252', 'UTF-8']
            )
            ->willReturnOnConsecutiveCalls($stream, $stream2, $stream3, $stream4);
        $this->mockStreamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });
        $this->instance->setContentStream($stream);

        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $manager = $this->instance;
        $this->assertEquals('test', $manager->getContentStream($streamPartMock, null, 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream($streamPartMock, null, 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test2', $manager->getContentStream($streamPartMock, null, 'US-ASCII', 'WINDOWS-1252')->getContents());
        $this->assertEquals('test3', $manager->getContentStream($streamPartMock, null, 'ISO-8859-1', 'WINDOWS-1252')->getContents());
        $this->assertEquals('test3', $manager->getContentStream($streamPartMock, null, 'ISO-8859-1', 'WINDOWS-1252')->getContents());
        $this->assertEquals('test4', $manager->getContentStream($streamPartMock, null, 'WINDOWS-1252', 'UTF-8')->getContents());
    }

    public function testGetContentStreamWithCharsetAndTransferEncoding() : void
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newCharsetStream')
            ->with($this->anything(), 'US-ASCII', 'UTF-8')
            ->willReturn($stream);
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('getTransferEncodingDecoratedStream')
            ->with($stream, 'quoted-printable')
            ->willReturn($stream);
        $this->mockStreamFactory->expects($this->atLeastOnce())
            ->method('newDecoratedMessagePartStream')
            ->willReturnCallback(function ($arg, $arg2) {
                return new MessagePartStreamDecorator($arg, $arg2);
            });

        $this->instance->setContentStream($stream);

        $streamPartMock = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMessagePart::class);
        $manager = $this->instance;
        $this->assertEquals('test', $manager->getContentStream($streamPartMock, 'quoted-printable', 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream($streamPartMock, 'quoted-printable', 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream($streamPartMock, 'quoted-printable', 'US-ASCII', 'UTF-8')->getContents());
    }
}
