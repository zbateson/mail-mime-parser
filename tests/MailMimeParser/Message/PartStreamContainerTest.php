<?php
namespace ZBateson\MailMimeParser\Message;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

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
    private $instance = null;
    private $mockStreamFactory = null;

    protected function legacySetUp()
    {
        $this->mockStreamFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')->getMock();
        $this->instance = new PartStreamContainer($this->mockStreamFactory);
    }

    public function testSetAndGetStream()
    {
        $stream = $this->getMockForAbstractClass('Psr\Http\Message\StreamInterface', [], '', false);
        $this->instance->setStream($stream);
        $stream->expects($this->once())->method('rewind');
        $this->assertSame($stream, $this->instance->getStream());
    }

    public function testSetContentStreamAndHasContent()
    {
        $stream = $this->getMockForAbstractClass('Psr\Http\Message\StreamInterface', [], '', false);
        $this->assertFalse($this->instance->hasContent());
        $this->assertNull($this->instance->getContentStream('', '', ''));
        $this->assertNull($this->instance->getBinaryContentStream(''));
        $this->instance->setContentStream($stream);
        $this->assertTrue($this->instance->hasContent());
    }

    public function testGetBinaryStream()
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newQuotedPrintableStream')
            ->with($stream)
            ->willReturn($stream);
        $stream->rewind();

        $stream2 = Psr7\Utils::streamFor('test2');
        $stream3 = Psr7\Utils::streamFor('test3');
        $this->mockStreamFactory->expects($this->exactly(2))
            ->method('newUUStream')
            ->with($stream)
            ->willReturnOnConsecutiveCalls($stream2, $stream3);
        $this->instance->setContentStream($stream);

        $manager = $this->instance;
        $this->assertEquals('test2', $manager->getBinaryContentStream('x-uuencode')->getContents());
        $this->assertEquals('test2', $manager->getBinaryContentStream('x-uuencode')->getContents());
        $this->assertEquals('test2', $manager->getBinaryContentStream('x-uuencode')->getContents());

        $this->assertEquals('test', $manager->getBinaryContentStream('quoted-printable')->getContents());
        $this->assertEquals('test', $manager->getBinaryContentStream('quoted-printable')->getContents());

        $this->assertEquals('test3', $manager->getBinaryContentStream('x-uuencode')->getContents());
    }

    public function testGetContentStreamWithQuotedPrintableDecoderTransferEncoding()
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newQuotedPrintableStream')
            ->with($stream)
            ->willReturn($stream);
        $this->assertNull($this->instance->getContentStream('quoted-printable', null, null));

        $this->instance->setContentStream($stream);
        $managerStream = $this->instance->getContentStream('quoted-printable', null, null);
        $this->assertInstanceOf('\GuzzleHttp\Psr7\CachingStream', $managerStream);
        $this->assertEquals('test', $managerStream->getContents());
    }

    public function testGetContentStreamWithBase64DecoderTransferEncoding()
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newBase64Stream')
            ->with($stream)
            ->willReturn($stream);
        $this->instance->setContentStream($stream);
        $managerStream = $this->instance->getContentStream('base64', null, null);
        $this->assertInstanceOf('\GuzzleHttp\Psr7\CachingStream', $managerStream);
        $this->assertEquals('test', $managerStream->getContents());
    }

    public function testGetContentStreamWithUUDecoderTransferEncoding()
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newUUStream')
            ->with($stream)
            ->willReturn($stream);
        $this->instance->setContentStream($stream);
        $managerStream = $this->instance->getContentStream('x-uuencode', null, null);
        $this->assertInstanceOf('\GuzzleHttp\Psr7\CachingStream', $managerStream);
        $this->assertEquals('test', $managerStream->getContents());
    }

    public function testGetContentStreamWithCharsetEncoding()
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newCharsetStream')
            ->with($stream, 'US-ASCII', 'UTF-8')
            ->willReturn($stream);
        $this->instance->setContentStream($stream);
        $managerStream = $this->instance->getContentStream(null, 'US-ASCII', 'UTF-8');
        $this->assertInstanceOf('\GuzzleHttp\Psr7\CachingStream', $managerStream);
        $this->assertEquals('test', $managerStream->getContents());
    }

    public function testGetContentStreamWithReAttachedTransferEncodingDecoder()
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newQuotedPrintableStream')
            ->with($stream)
            ->willReturn($stream);
        $stream->rewind();

        $stream2 = Psr7\Utils::streamFor('test2');
        $stream3 = Psr7\Utils::streamFor('test3');
        $this->mockStreamFactory->expects($this->exactly(2))
            ->method('newUUStream')
            ->with($stream)
            ->willReturnOnConsecutiveCalls($stream2, $stream3);
        $this->instance->setContentStream($stream);

        $manager = $this->instance;
        $this->assertEquals('test2', $manager->getContentStream('x-uuencode', null, null)->getContents());
        $this->assertEquals('test2', $manager->getContentStream('x-uuencode', null, null)->getContents());
        $this->assertEquals('test2', $manager->getContentStream('x-uuencode', null, null)->getContents());

        $this->assertEquals('test', $manager->getContentStream('quoted-printable', null, null)->getContents());
        $this->assertEquals('test', $manager->getContentStream('quoted-printable', null, null)->getContents());

        $this->assertEquals('test3', $manager->getContentStream('x-uuencode', null, null)->getContents());
    }

    public function testGetContentStreamWithReAttachedCharsetConversionDecoder()
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(4))
            ->method('newCharsetStream')
            ->withConsecutive(
                [$stream, 'US-ASCII', 'UTF-8'],
                [$stream, 'US-ASCII', 'WINDOWS-1252'],
                [$stream, 'ISO-8859-1', 'WINDOWS-1252'],
                [$stream, 'WINDOWS-1252', 'UTF-8']
            )
            ->willReturn($stream);
        $this->instance->setContentStream($stream);

        $manager = $this->instance;
        $this->assertEquals('test', $manager->getContentStream(null, 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream(null, 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream(null, 'US-ASCII', 'WINDOWS-1252')->getContents());
        $this->assertEquals('test', $manager->getContentStream(null, 'ISO-8859-1', 'WINDOWS-1252')->getContents());
        $this->assertEquals('test', $manager->getContentStream(null, 'ISO-8859-1', 'WINDOWS-1252')->getContents());
        $this->assertEquals('test', $manager->getContentStream(null, 'WINDOWS-1252', 'UTF-8')->getContents());
    }

    public function testGetContentStreamWithCharsetAndTransferEncoding()
    {
        $stream = Psr7\Utils::streamFor('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newCharsetStream')
            ->with($this->anything(), 'US-ASCII', 'UTF-8')
            ->willReturn($stream);
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newQuotedPrintableStream')
            ->with($stream)
            ->willReturn($stream);
        $this->instance->setContentStream($stream);

        $manager = $this->instance;
        $this->assertEquals('test', $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8')->getContents());
    }
}
