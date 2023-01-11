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
        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
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
            ->method('getStreamPartStartPos')
            ->willReturn(4);

        $partBuilder->expects($this->atLeastOnce())
            ->method('getStream')
            ->willReturn(Psr7\Utils::streamFor('test'));


        $factory = new StreamFactory();

        $this->assertInstanceOf('ZBateson\StreamDecorators\SeekingLimitStream', $factory->getLimitedPartStream($partBuilder));
        $this->assertInstanceOf('ZBateson\StreamDecorators\SeekingLimitStream', $factory->getLimitedContentStream($partBuilder));
        $this->assertNull($factory->getLimitedContentStream($partBuilder));

        $this->assertInstanceOf('ZBateson\StreamDecorators\NonClosingStream', $factory->newNonClosingStream(Psr7\Utils::streamFor('test')));
        $this->assertInstanceOf('ZBateson\StreamDecorators\ChunkSplitStream', $factory->newChunkSplitStream(Psr7\Utils::streamFor('test')));
        $this->assertInstanceOf('ZBateson\StreamDecorators\Base64Stream', $factory->newBase64Stream(Psr7\Utils::streamFor('test')));
        $this->assertInstanceOf('ZBateson\StreamDecorators\QuotedPrintableStream', $factory->newQuotedPrintableStream(Psr7\Utils::streamFor('test')));
        $this->assertInstanceOf('ZBateson\StreamDecorators\UUStream', $factory->newUUStream(Psr7\Utils::streamFor('test')));
        $this->assertInstanceOf('ZBateson\StreamDecorators\CharsetStream', $factory->newCharsetStream(Psr7\Utils::streamFor('test'), 'utf-8', 'utf-16'));

        $mockMimePart = $this->getMockBuilder('ZBateson\MailMimeParser\Message\MimePart')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Stream\MessagePartStream', $factory->newMessagePartStream($mockMimePart));
        $this->assertInstanceOf('ZBateson\MailMimeParser\Stream\HeaderStream', $factory->newHeaderStream($mockMimePart));
    }
}
