<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use Psr\Http\Message\StreamInterface;
use SplObserver;
use SplSubject;

/**
 * A part stream container that proxies requests for content streams to a parser
 * to read the content.
 *
 * Keeps reference to the original stream a message was parsed from, using that
 * stream as the message's stream instead of the parent's MessagePartStream
 * unless the part changed.
 * 
 * The container must also be attached to its underlying part with
 * SplSubject::attach() so the ParserPartStreamContainer gets notified of any
 * changes.
 *
 * @author Zaahid Bateson
 */
class ParserPartStreamContainer extends PartStreamContainer implements SplObserver
{
    /**
     * @var ParserPartProxy
     */
    protected $parserProxy;

    /**
     * @var StreamInterface the original stream for a parsed message, used when
     *      the message hasn't changed
     */
    protected $parsedStream;

    /**
     * @var bool true if the stream should be detached when this container is
     *      destroyed.
     */
    protected $detachParsedStream = false;

    /**
     * @var bool set to true if the part's been updated since it was created.
     */
    protected $partUpdated = false;

    /**
     * @var bool false if the content for the part represented by this container
     *      has not yet been requested from the parser.
     */
    protected $contentParseRequested = false;

    public function __construct(StreamFactory $streamFactory, ParserPartProxy $parserProxy)
    {
        parent::__construct($streamFactory);
        $this->parserProxy = $parserProxy;
    }

    public function __destruct()
    {
        if ($this->detachParsedStream && $this->parsedStream !== null) {
            $this->parsedStream->detach();
        }
    }

    /**
     * Requests content from the parser if not previously requested.
     */
    protected function requestParsedContentStream()
    {
        if (!$this->contentParseRequested) {
            $this->contentParseRequested = true;
            $this->parserProxy->parseContent();
            parent::setContentStream($this->streamFactory->getLimitedContentStream(
                $this->parserProxy->getPartBuilder()
            ));
        }
    }

    /**
     * Ensures the parser has parsed the entire part, and sets
     * $this->parsedStream to the original parsed stream (or a limited part of
     * it corresponding to the current part this stream container belongs to).
     */
    protected function requestParsedStream()
    {
        if ($this->parsedStream === null) {
            $this->parserProxy->parseAll();
            $this->parsedStream = $this->streamFactory->getLimitedPartStream(
                $this->parserProxy->getPartBuilder()->getStream(),
                $this->parserProxy->getPartBuilder()
            );
            if ($this->parsedStream !== null) {
                $this->detachParsedStream = $this->parsedStream->getMetadata('mmp-detached-stream');
            }
        }
    }

    public function hasContent()
    {
        $this->requestParsedContentStream();
        return parent::hasContent();
    }

    public function getContentStream($transferEncoding, $fromCharset, $toCharset)
    {
        $this->requestParsedContentStream();
        return parent::getContentStream($transferEncoding, $fromCharset, $toCharset);
    }

    public function setContentStream(StreamInterface $contentStream = null)
    {
        $this->requestParsedContentStream();
        parent::setContentStream($contentStream);
    }

    public function getStream()
    {
        if (!$this->partUpdated) {
            $this->requestParsedStream();
            if ($this->parsedStream !== null) {
                $this->parsedStream->rewind();
                return $this->parsedStream;
            }
        }
        return parent::getStream();
    }

    public function update(SplSubject $subject)
    {
        $this->partUpdated = true;
    }
}
