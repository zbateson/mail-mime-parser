<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use ZBateson\MailMimeParser\Message\Part\ParentHeaderPart;
use ZBateson\MailMimeParser\Message\Part\MessagePart;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7;

/**
 * Psr7 stream decorator implementation providing a readable stream for a part's
 * headers.
 *
 * HeaderStream is only used by a MimePart parent.  It can accept any
 * MessagePart - for non-MimeParts, only type headers are generated based on
 * available information.
 *
 * @author Zaahid Bateson
 */
class HeaderStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /**
     * @var MessagePart the part to read from.
     */
    protected $part;

    /**
     * @param MessagePart $part
     */
    public function __construct(MessagePart $part)
    {
        $this->part = $part;
    }

    /**
     * Returns a header array for the current part.
     *
     * If the part is not a MimePart, Content-Type, Content-Disposition and
     * Content-Transfer-Encoding headers are generated manually.
     *
     * @return array
     */
    private function getPartHeadersArray()
    {
        if ($this->part instanceof ParentHeaderPart) {
            return $this->part->getRawHeaders();
        } elseif ($this->part->getParent() !== null && $this->part->getParent()->isMime()) {
            return [
                [ 'Content-Type', $this->part->getContentType() ],
                [ 'Content-Disposition', $this->part->getContentDisposition() ],
                [ 'Content-Transfer-Encoding', $this->part->getContentTransferEncoding() ]
            ];
        }
        return [];
    }

    /**
     * Writes out headers for $this->part and follows them with an empty line.
     *
     * @param StreamInterface $stream
     */
    public function writePartHeadersTo(StreamInterface $stream)
    {
        $headers = $this->getPartHeadersArray();
        foreach ($headers as $header) {
            $stream->write("${header[0]}: ${header[1]}\r\n");
        }
        $stream->write("\r\n");
    }

    /**
     * Creates the underlying stream lazily when required.
     *
     * @return StreamInterface
     */
    protected function createStream()
    {
        $stream = Psr7\stream_for();
        $this->writePartHeadersTo($stream);
        $stream->rewind();
        return $stream;
    }
}
