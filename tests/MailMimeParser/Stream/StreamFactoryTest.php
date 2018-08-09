<?php
namespace ZBateson\MailMimeParser\Stream;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * StreamFactoryTest
 *
 * @group StreamFactory
 * @group Stream
 * @covers ZBateson\MailMimeParser\Stream\StreamFactory
 * @author Zaahid Bateson
 */
class StreamFactoryTest extends TestCase
{
    public function testNewInstance()
    {
        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $partBuilder->expects($this->once())
            ->method('getStreamPartLength')
            ->willReturn(0);
        $partBuilder->expects($this->once())
            ->method('getStreamPartStartOffset')
            ->willReturn(10);

        $partBuilder->expects($this->exactly(3))
            ->method('getStreamContentLength')
            ->willReturnOnConsecutiveCalls(2, 2, 0);
        $partBuilder->expects($this->once())
            ->method('getStreamContentStartOffset')
            ->willReturn(4);

        $factory = new StreamFactory();

        $this->assertInstanceOf('ZBateson\StreamDecorators\SeekingLimitStream', $factory->getLimitedPartStream(Psr7\stream_for('test'), $partBuilder));
        $this->assertInstanceOf('ZBateson\StreamDecorators\SeekingLimitStream', $factory->getLimitedContentStream(Psr7\stream_for('test'), $partBuilder));
        $this->assertNull($factory->getLimitedContentStream(Psr7\stream_for('test'), $partBuilder));

        $this->assertInstanceOf('ZBateson\StreamDecorators\NonClosingStream', $factory->newNonClosingStream(Psr7\stream_for('test')));
        $this->assertInstanceOf('ZBateson\StreamDecorators\ChunkSplitStream', $factory->newChunkSplitStream(Psr7\stream_for('test')));
        $this->assertInstanceOf('ZBateson\StreamDecorators\Base64Stream', $factory->newBase64Stream(Psr7\stream_for('test')));
        $this->assertInstanceOf('ZBateson\StreamDecorators\QuotedPrintableStream', $factory->newQuotedPrintableStream(Psr7\stream_for('test')));
        $this->assertInstanceOf('ZBateson\StreamDecorators\UUStream', $factory->newUUStream(Psr7\stream_for('test')));
        $this->assertInstanceOf('ZBateson\StreamDecorators\CharsetStream', $factory->newCharsetStream(Psr7\stream_for('test'), 'utf-8', 'utf-16'));

        $mockMimePart = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Stream\MessagePartStream', $factory->newMessagePartStream($mockMimePart));
        $this->assertInstanceOf('ZBateson\MailMimeParser\Stream\HeaderStream', $factory->newHeaderStream($mockMimePart));
    }
}
