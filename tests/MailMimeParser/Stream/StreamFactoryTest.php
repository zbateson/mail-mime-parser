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
    public function testNewInstance() : void
    {
        $partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $partBuilder->expects($this->once())
            ->method('getStreamPartLength')
            ->willReturn(0);
        $partBuilder->expects($this->once())
            ->method('getStreamPartStartPos')
            ->willReturn(10);

        $partBuilder->expects($this->exactly(3))
            ->method('getStreamContentLength')
            ->willReturnOnConsecutiveCalls(2, 2, 0);
        $partBuilder->expects($this->once())
            ->method('getStreamContentStartPos')
            ->willReturn(4);

        $partBuilder->expects($this->atLeastOnce())
            ->method('getStream')
            ->willReturn(Psr7\Utils::streamFor('test'));


        $factory = new StreamFactory();

        $this->assertInstanceOf(\ZBateson\StreamDecorators\SeekingLimitStream::class, $factory->getLimitedPartStream($partBuilder));
        $this->assertInstanceOf(\ZBateson\StreamDecorators\SeekingLimitStream::class, $factory->getLimitedContentStream($partBuilder));
        $this->assertNull($factory->getLimitedContentStream($partBuilder));

        $this->assertInstanceOf(\ZBateson\StreamDecorators\NonClosingStream::class, $factory->newNonClosingStream(Psr7\Utils::streamFor('test')));
        $this->assertInstanceOf(\ZBateson\StreamDecorators\ChunkSplitStream::class, $factory->newChunkSplitStream(Psr7\Utils::streamFor('test')));
        $this->assertInstanceOf(\ZBateson\StreamDecorators\Base64Stream::class, $factory->newBase64Stream(Psr7\Utils::streamFor('test')));
        $this->assertInstanceOf(\ZBateson\StreamDecorators\QuotedPrintableStream::class, $factory->newQuotedPrintableStream(Psr7\Utils::streamFor('test')));
        $this->assertInstanceOf(\ZBateson\StreamDecorators\UUStream::class, $factory->newUUStream(Psr7\Utils::streamFor('test')));
        $this->assertInstanceOf(\ZBateson\StreamDecorators\CharsetStream::class, $factory->newCharsetStream(Psr7\Utils::streamFor('test'), 'utf-8', 'utf-16'));

        $mockMimePart = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\MimePart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Stream\MessagePartStream::class, $factory->newMessagePartStream($mockMimePart));
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Stream\HeaderStream::class, $factory->newHeaderStream($mockMimePart));
    }
}
