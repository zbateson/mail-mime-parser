<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message\IMessagePart;
use Psr\Http\Message\StreamInterface;
use SplObserver;

/**
 * Ferries calls to an IMessagePart.
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
trait MessagePartDecoratorTrait
{
    /**
     * @var IMessagePart The underlying part to wrap.
     */
    protected $part;

    public function __construct(IMessagePart $part)
    {
        $this->part = $part;
    }

    public function attach(SplObserver $observer)
    {
        $this->part->attach($observer);
    }

    public function detach(SplObserver $observer)
    {
        $this->part->detach($observer);
    }

    public function notify()
    {
        $this->part->notify();
    }

    public function hasContent()
    {
        return $this->part->hasContent();
    }

    public function isTextPart()
    {
        return $this->part->isTextPart();
    }

    public function getContentType()
    {
        return $this->part->getContentType();
    }

    public function getCharset()
    {
        return $this->part->getCharset();
    }

    public function getContentDisposition()
    {
        return $this->part->getContentDisposition();
    }

    public function getContentTransferEncoding()
    {
        return $this->part->getContentTransferEncoding();
    }

    public function getFilename()
    {
        return $this->part->getFilename();
    }

    public function isMime()
    {
        return $this->part->isMime();
    }

    public function getContentId()
    {
        return $this->part->getContentId();
    }

    public function getResourceHandle()
    {
        return $this->part->getResourceHandle();
    }

    public function getStream()
    {
        return $this->part->getStream();
    }

    public function setCharsetOverride($charsetOverride, $onlyIfNoCharset = false)
    {
        $this->part->setCharsetOverride($charsetOverride, $onlyIfNoCharset);
    }

    public function getContentResourceHandle($charset = MailMimeParser::DEFAULT_CHARSET)
    {
        // deprecated 1.2.1 (remove it)
        return $this->part->getContentResourceHandle($charset);
    }

    public function getContentStream($charset = MailMimeParser::DEFAULT_CHARSET)
    {
        return $this->part->getContentStream($charset);
    }

    public function getBinaryContentStream()
    {
        return $this->part->getBinaryContentStream();
    }

    public function getBinaryContentResourceHandle()
    {
        return $this->part->getBinaryContentResourceHandle();
    }

    public function saveContent($filenameResourceOrStream)
    {
        $this->part->saveContent($filenameResourceOrStream);
    }

    public function getContent($charset = MailMimeParser::DEFAULT_CHARSET)
    {
        return $this->part->getContent($charset);
    }

    public function getParent()
    {
        return $this->part->getParent();
    }

    public function attachContentStream(StreamInterface $stream, $streamCharset = MailMimeParser::DEFAULT_CHARSET)
    {
        $this->part->attachContentStream($stream, $streamCharset);
    }

    public function detachContentStream()
    {
        $this->part->detachContentStream();
    }

    public function setContent($resource, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $this->part->setContent($resource, $charset);
    }

    public function save($filenameResourceOrStream)
    {
        $this->part->save($filenameResourceOrStream);
    }

    public function __toString()
    {
        return $this->part->__toString();
    }
}
