<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Message\IMultiPart;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use SplObserver;
use SplSubject;

/**
 * Provides a readable stream for a MessagePart.
 *
 * @author Zaahid Bateson
 */
class MessagePartStream implements StreamInterface, SplObserver
{
    use StreamDecoratorTrait;

    /**
     * @var StreamFactory For creating needed stream decorators.
     */
    protected $streamFactory;

    /**
     * @var IMessagePart The part to read from.
     */
    protected $part;

    protected $appendStream = null;

    /**
     * Constructor
     * 
     * @param StreamFactory $sdf
     * @param IMessagePart $part
     */
    public function __construct(StreamFactory $sdf, IMessagePart $part)
    {
        $this->streamFactory = $sdf;
        $this->part = $part;
        $part->attach($this);
    }

    public function __destruct()
    {
        if ($this->part !== null) {
            $this->part->detach($this);
        }
    }

    public function update(SplSubject $subject)
    {
        if ($this->appendStream !== null) {
            $this->appendStream = null;
        }
    }

    /**
     * Attaches and returns a CharsetStream decorator to the passed $stream.
     *
     * If the current attached IMessagePart doesn't specify a charset, $stream
     * is returned as-is.
     *
     * @param StreamInterface $stream
     * @return StreamInterface
     */
    private function getCharsetDecoratorForStream(StreamInterface $stream)
    {
        $charset = $this->part->getCharset();
        if (!empty($charset)) {
            $stream = $this->streamFactory->newCharsetStream(
                $stream,
                $charset,
                MailMimeParser::DEFAULT_CHARSET
            );
        }
        return $stream;
    }
    
    /**
     * Attaches and returns a transfer encoding stream decorator to the passed
     * $stream.
     *
     * The attached stream decorator is based on the attached part's returned
     * value from MessagePart::getContentTransferEncoding, using one of the
     * following stream decorators as appropriate:
     *
     * o QuotedPrintableStream
     * o Base64Stream
     * o UUStream
     *
     * @param StreamInterface $stream
     * @return StreamInterface
     */
    private function getTransferEncodingDecoratorForStream(StreamInterface $stream)
    {
        $encoding = $this->part->getContentTransferEncoding();
        $decorator = null;
        switch ($encoding) {
            case 'quoted-printable':
                $decorator = $this->streamFactory->newQuotedPrintableStream($stream);
                break;
            case 'base64':
                $decorator = $this->streamFactory->newBase64Stream(
                    $this->streamFactory->newChunkSplitStream($stream));
                break;
            case 'x-uuencode':
                $decorator = $this->streamFactory->newUUStream($stream);
                $decorator->setFilename($this->part->getFilename());
                break;
            default:
                return $stream;
        }
        return $decorator;
    }

    /**
     * Writes out the content portion of the attached mime part to the passed
     * $stream.
     *
     * @param StreamInterface $stream
     */
    private function writePartContentTo(StreamInterface $stream)
    {
        $contentStream = $this->part->getContentStream();
        if ($contentStream !== null) {
            $copyStream = $this->streamFactory->newNonClosingStream($stream);
            $es = $this->getTransferEncodingDecoratorForStream($copyStream);
            $cs = $this->getCharsetDecoratorForStream($es);
            Psr7\copy_to_stream($contentStream, $cs);
            $cs->close();
        }
    }

    /**
     * Creates an array of streams based on the attached part's mime boundary
     * and child streams.
     *
     * @param IMultiPart $part passed in because $this->part is declared
     *        as IMessagePart
     * @return StreamInterface[]
     */
    protected function getBoundaryAndChildStreams(IMultiPart $part)
    {
        $boundary = $part->getHeaderParameter('Content-Type', 'boundary');
        if ($boundary === null) {
            return array_map(
                function ($child) {
                    return $child->getStream();
                },
                $part->getChildParts()
            );
        }
        $streams = [];
        foreach ($part->getChildParts() as $i => $child) {
            if ($i !== 0 || $part->hasContent()) {
                $streams[] = Psr7\stream_for("\r\n");
            }
            $streams[] = Psr7\stream_for("--$boundary\r\n");
            $streams[] = $child->getStream();
        }
        $streams[] = Psr7\stream_for("\r\n--$boundary--\r\n");
        
        return $streams;
    }

    /**
     * Returns an array of Psr7 Streams representing the attached part and it's
     * direct children.
     *
     * @return StreamInterface[]
     */
    protected function getStreamsArray()
    {
        $content = Psr7\stream_for();
        $this->writePartContentTo($content);
        $content->rewind();
        $streams = [ $this->streamFactory->newHeaderStream($this->part), $content ];

        if ($this->part instanceof IMultiPart && $this->part->getChildCount()) {
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
        if ($this->appendStream === null) {
            $this->appendStream = new AppendStream($this->getStreamsArray());
        }
        return $this->appendStream;
    }
}
