<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Stream;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\StreamDecorators\Base64Stream;
use ZBateson\StreamDecorators\CharsetStream;
use ZBateson\StreamDecorators\ChunkSplitStream;
use ZBateson\StreamDecorators\NonClosingStream;
use ZBateson\StreamDecorators\PregReplaceFilterStream;
use ZBateson\StreamDecorators\QuotedPrintableStream;
use ZBateson\StreamDecorators\SeekingLimitStream;
use ZBateson\StreamDecorators\UUStream;

/**
 * Factory class for Psr7 stream decorators used in MailMimeParser.
 *
 * @author Zaahid Bateson
 */
class StreamFactory
{
    /**
     * Returns a SeekingLimitStream using $part->getStreamPartLength() and
     * $part->getStreamPartStartPos()
     */
    public function getLimitedPartStream(PartBuilder $part) : StreamInterface
    {
        return $this->newLimitStream(
            $part->getStream(),
            $part->getStreamPartLength(),
            $part->getStreamPartStartPos()
        );
    }

    /**
     * Returns a SeekingLimitStream using $part->getStreamContentLength() and
     * $part->getStreamContentStartPos()
     */
    public function getLimitedContentStream(PartBuilder $part) : ?StreamInterface
    {
        $length = $part->getStreamContentLength();
        if ($length !== 0) {
            return $this->newLimitStream(
                $part->getStream(),
                $part->getStreamContentLength(),
                $part->getStreamContentStartPos()
            );
        }
        return null;
    }

    /**
     * Creates and returns a SeekingLimitedStream.
     */
    private function newLimitStream(StreamInterface $stream, int $length, int $start) : StreamInterface
    {
        return new SeekingLimitStream(
            $this->newNonClosingStream($stream),
            $length,
            $start
        );
    }

    /**
     * Creates a non-closing stream that doesn't close it's internal stream when
     * closing/detaching.
     */
    public function newNonClosingStream(StreamInterface $stream) : StreamInterface
    {
        return new NonClosingStream($stream);
    }

    /**
     * Creates a ChunkSplitStream.
     */
    public function newChunkSplitStream(StreamInterface $stream) : StreamInterface
    {
        return new ChunkSplitStream($stream);
    }

    /**
     * Creates and returns a Base64Stream with an internal
     * PregReplaceFilterStream that filters out non-base64 characters.
     */
    public function newBase64Stream(StreamInterface $stream) : StreamInterface
    {
        return new Base64Stream(
            new PregReplaceFilterStream($stream, '/[^a-zA-Z0-9\/\+=]/', '')
        );
    }

    /**
     * Creates and returns a QuotedPrintableStream.
     */
    public function newQuotedPrintableStream(StreamInterface $stream) : StreamInterface
    {
        return new QuotedPrintableStream($stream);
    }

    /**
     * Creates and returns a UUStream
     */
    public function newUUStream(StreamInterface $stream) : StreamInterface
    {
        return new UUStream($stream);
    }

    /**
     * Creates and returns a CharsetStream
     */
    public function newCharsetStream(StreamInterface $stream, string $fromCharset, string $toCharset) : StreamInterface
    {
        return new CharsetStream($stream, $fromCharset, $toCharset);
    }

    /**
     * Creates and returns a MessagePartStream
     */
    public function newMessagePartStream(IMessagePart $part) : StreamInterface
    {
        return new MessagePartStream($this, $part);
    }

    /**
     * Creates and returns a HeaderStream
     */
    public function newHeaderStream(IMessagePart $part) : StreamInterface
    {
        return new HeaderStream($part);
    }
}
