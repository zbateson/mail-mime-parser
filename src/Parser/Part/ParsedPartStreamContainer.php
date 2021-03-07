<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Parser\ParserProxy;
use Psr\Http\Message\StreamInterface;
use SplObserver;
use SplSubject;

/**
 * Keeps reference to the original stream a message was parsed from, using that
 * stream as the message's stream instead of the parent's MessagePartStream
 * unless the part changed.
 * 
 * The container must also be attached to its underlying part with
 * SplSubject::attach() so the ParsedPartStreamContainer gets notified of any
 * changes.
 *
 * @author Zaahid Bateson
 */
class ParsedPartStreamContainer extends PartStreamContainer implements SplObserver
{
    /**
     * @var ParserProxy
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
     * @var bool true if the part changed and the parent stream should be used,
     *      initialized to true and set to false when the parsed stream is set
     *      in setParsedStream
     */
    protected $useParentStream = true;

    /**
     * @var bool
     */
    protected $partUpdated = false;

    /**
     * @var bool false if the content for the part represented by this container
     *      has not yet been requested from the parser.
     */
    protected $contentParseRequested = false;

    /**
     * @var bool false if the the part represented by this container has not yet
     *      been fully parsed.
     */
    protected $partParsed = false;

    public function __destruct()
    {
        if ($this->detachParsedStream && $this->parsedStream !== null) {
            $this->parsedStream->detach();
        }
    }

    public function setProxyParser(ParserProxy $proxy)
    {
        $this->parserProxy = $proxy;
    }
    
    protected function requestParsedContentStream()
    {
        if (!$this->contentParseRequested) {
            $this->contentParseRequested = true;
            $this->parserProxy->readContent();
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

    public function setParsedStream(StreamInterface $parsedStream)
    {
        $this->parsedStream = $parsedStream;
        if ($parsedStream !== null) {
            $this->detachParsedStream = $parsedStream->getMetadata('mmp-detached-stream');
            $this->useParentStream = !$this->partUpdated;
        }
        $this->partParsed = true;
    }

    public function setParsedContentStream(StreamInterface $contentStream = null)
    {
        $this->contentParsed = true;
        $this->setContentStream($contentStream);
    }

    public function getStream()
    {
        if ($this->useParentStream || $this->parsedStream === null) {
            return parent::getStream();
        }
        $this->parsedStream->rewind();
        return $this->parsedStream;
    }

    public function update(SplSubject $subject)
    {
        $this->useParentStream = true;
        $this->partUpdated = true;
    }
}
