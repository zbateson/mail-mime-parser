<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message\Part\MessagePart;
use ZBateson\MailMimeParser\Message\Part\MimePart;
use ZBateson\MailMimeParser\Message\Part\ParentHeaderPart;
use ZBateson\MailMimeParser\Stream\StreamDecoratorFactory;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7;

/**
 * Writes a MimePart to a resource handle.
 * 
 * The class is responsible for writing out the headers and content of a
 * MimePart to an output stream buffer, taking care of encoding and filtering.
 * 
 * @author Zaahid Bateson
 */
class MessagePartStream implements StreamInterface
{
    use StreamDecoratorTrait;

    protected $streamDecoratorFactory;
    protected $part;

    public function __construct(StreamDecoratorFactory $sdf, MessagePart $part)
    {
        $this->streamDecoratorFactory = $sdf;
        $this->part = $part;
    }

    /**
     * Sets up a mailmimeparser-encode stream filter on the content resource 
     * handle of the passed MimePart if applicable and returns a reference to
     * the filter.
     *
     * @param MimePart $part
     * @return StreamInterface a reference to the appended stream filter or null
     */
    private function getCharsetDecoratorForStream(MessagePart $part, StreamInterface $stream)
    {
        $charset = $part->getCharset();
        if (!empty($charset)) {
            $decorator = $this->streamDecoratorFactory->newCharsetStream(
                $stream,
                $charset,
                MailMimeParser::DEFAULT_CHARSET
            );
            return $decorator;
        }
        return $stream;
    }
    
    /**
     * Appends a stream filter on the passed MimePart's content resource handle
     * based on the type of encoding for the passed part.
     *
     * @param MimePart $part
     * @param resource $handle
     * @param StreamLeftover $leftovers
     * @return StreamInterface the stream filter
     */
    private function getTransferEncodingDecoratorForStream(
        MessagePart $part,
        StreamInterface $stream
    ) {
        $encoding = $part->getContentTransferEncoding();
        $decorator = null;
        switch ($encoding) {
            case 'quoted-printable':
                $decorator = $this->streamDecoratorFactory->newQuotedPrintableStream($stream);
                break;
            case 'base64':
                $decorator = $this->streamDecoratorFactory->newBase64Stream($stream);
                break;
            case 'x-uuencode':
                $decorator = $this->streamDecoratorFactory->newUUStream($stream);
                $decorator->setFilename($part->getFilename());
                break;
            default:
                return $stream;
        }
        return $decorator;
    }

    /**
     * Writes out the content portion of the mime part based on the headers that
     * are set on the part, taking care of character/content-transfer encoding.
     *
     * @param MessagePart $part
     * @param StreamInterface $stream
     */
    public function writePartContentTo(MessagePart $part, StreamInterface $stream)
    {
        $contentStream = $part->getContentStream();
        if ($contentStream !== null) {
            $copyStream = $this->streamDecoratorFactory->newNonClosingStream($stream);
            $es = $this->getTransferEncodingDecoratorForStream(
                $part,
                $copyStream
            );
            $cs = $this->getCharsetDecoratorForStream($part, $es);
            Psr7\copy_to_stream($contentStream, $cs);
            $cs->close();
        }
    }

    protected function getBoundaryAndChildStreams(ParentHeaderPart $part)
    {
        $streams = [];
        $boundary = $part->getHeaderParameter('Content-Type', 'boundary');
        foreach ($part->getChildParts() as $child) {
            if ($boundary !== null) {
                $streams[] = Psr7\stream_for("\r\n--$boundary\r\n");
            }
            $streams[] = $child->getStream();
        }
        if ($boundary !== null) {
            $streams[] = Psr7\stream_for("\r\n--$boundary--\r\n");
        }
    }

    protected function getStreamsArray()
    {
        $content = Psr7\stream_for('php://temp');
        $this->writePartContentTo($this->part, $content);
        $content->rewind();
        $streams = [ new HeaderStream($this->part), $content ];

        if ($this->part instanceof ParentHeaderPart) {
            $streams = array_merge($streams, $this->getBoundaryAndChildStreams($this->part));
        }

        return $streams;
    }

    /**
     * Creates the underlying stream lazily when required.
     *
     * @return StreamInterface
     */
    protected function createStream()
    {
        return new AppendStream($this->getStreamsArray());
    }
}
