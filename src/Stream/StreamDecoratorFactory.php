<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use GuzzleHttp\Psr7\LimitStream;
use Psr\Http\Message\StreamInterface;
use ZBateson\StreamDecorators\Base64StreamDecorator;
use ZBateson\StreamDecorators\QuotedPrintableStreamDecorator;
use ZBateson\StreamDecorators\UUStreamDecorator;
use ZBateson\StreamDecorators\CharsetStreamDecorator;
use ZBateson\StreamDecorators\NonClosingLimitStream;
use ZBateson\MailMimeParser\Message\Part\PartBuilder;

/**
 * Factory class for Psr7 stream decorators used in MailMimeParser.
 *
 * @author Zaahid Bateson
 */
class StreamDecoratorFactory
{
    public function getLimitedPartStream(StreamInterface $stream, PartBuilder $part)
    {
        return $this->newLimitStreamDecorator(
            $stream,
            $part->getStreamPartLength(),
            $part->getStreamPartStartOffset()
        );
    }

    public function getLimitedContentStream(StreamInterface $stream, PartBuilder $part)
    {
        $length = $part->getStreamContentLength();
        if ($length !== 0) {
            return $this->newLimitStreamDecorator(
                $stream,
                $part->getStreamContentLength(),
                $part->getStreamContentStartOffset()
            );
        }
        return null;
    }

    private function newLimitStreamDecorator(StreamInterface $stream, $length, $start)
    {
        return new NonClosingLimitStream($stream, $length, $start);
    }

    public function newBase64StreamDecorator(StreamInterface $stream)
    {
        return new Base64StreamDecorator($stream);
    }

    public function newQuotedPrintableStreamDecorator(StreamInterface $stream)
    {
        return new QuotedPrintableStreamDecorator($stream);
    }

    public function newUUStreamDecorator(StreamInterface $stream)
    {
        return new UUStreamDecorator($stream);
    }

    public function newCharsetStreamDecorator(StreamInterface $stream, $fromCharset, $toCharset)
    {
        return new CharsetStreamDecorator($stream, $fromCharset, $toCharset);
    }
}
