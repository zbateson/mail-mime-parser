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
 * 
 *
 * @author Zaahid Bateson
 */
class HeaderStream implements StreamInterface
{
    use StreamDecoratorTrait;

    protected $part;

    public function __construct(MessagePart $part)
    {
        $this->part = $part;
    }

    private function getPartHeadersArray()
    {
        if ($this->part instanceof ParentHeaderPart) {
            return $this->part->getRawHeaders();
        } elseif ($this->part->getParent() !== null && $this->part->getParent()->isMime()) {
            return [
                'Content-Type' => $this->part->getContentType(),
                'Content-Disposition' => $this->part->getContentDisposition(),
                'Content-Transfer-Encoding' => $this->part->getContentTransferEncoding()
            ];
        }
        return [];
    }

    /**
     * Writes out the headers of the passed part and follows them with an
     * empty line.
     *
     * @param MimePart $part
     * @param StreamInterface $stream
     */
    public function writePartHeadersTo(StreamInterface $stream)
    {
        $headers = $this->getPartHeadersArray($this->part);
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
        $stream = Psr7\stream_for('php://temp');
        $this->writePartHeadersTo($stream);
        $stream->rewind();
        return $stream;
    }
}
