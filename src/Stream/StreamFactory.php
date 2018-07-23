<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use GuzzleHttp\Psr7\CachingStream;
use Psr\Http\Message\StreamInterface;
use ZBateson\StreamDecorators\SeekingLimitStream;
use ZBateson\StreamDecorators\Base64Stream;
use ZBateson\StreamDecorators\QuotedPrintableStream;
use ZBateson\StreamDecorators\UUStream;
use ZBateson\StreamDecorators\CharsetStream;
use ZBateson\StreamDecorators\NonClosingStream;
use ZBateson\StreamDecorators\PregReplaceFilterStream;
use ZBateson\MailMimeParser\Message\Part\PartBuilder;

/**
 * Factory class for Psr7 stream decorators used in MailMimeParser.
 *
 * @author Zaahid Bateson
 */
class StreamFactory
{
    public function getLimitedPartStream(StreamInterface $stream, PartBuilder $part)
    {
        return $this->newLimitStream(
            $stream,
            $part->getStreamPartLength(),
            $part->getStreamPartStartOffset()
        );
    }

    public function getLimitedContentStream(StreamInterface $stream, PartBuilder $part)
    {
        $length = $part->getStreamContentLength();
        if ($length !== 0) {
            return $this->newLimitStream(
                $stream,
                $part->getStreamContentLength(),
                $part->getStreamContentStartOffset()
            );
        }
        return null;
    }

    private function newLimitStream(StreamInterface $stream, $length, $start)
    {
        return new SeekingLimitStream(
            $this->newNonClosingStream($stream),
            $length,
            $start
        );
    }

    public function newNonClosingStream(StreamInterface $stream)
    {
        return new NonClosingStream($stream);
    }

    public function newBase64Stream(StreamInterface $stream)
    {
        return new CachingStream(
            new Base64Stream(
                new PregReplaceFilterStream($stream, '/[^a-zA-Z0-9\/\+=]/', '')
            )
        );
    }

    public function newQuotedPrintableStream(StreamInterface $stream)
    {
        return new CachingStream(new QuotedPrintableStream($stream));
    }

    public function newUUStream(StreamInterface $stream)
    {
        return new CachingStream(new UUStream($stream));
    }

    public function newCharsetStream(StreamInterface $stream, $fromCharset, $toCharset)
    {
        return new CachingStream(new CharsetStream($stream, $fromCharset, $toCharset));
    }

    public function newMessagePartStream(MessagePart $part)
    {
        return new MessagePartStream($this, $part);
    }
}
