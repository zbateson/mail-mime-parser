<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ArrayIterator;
use Iterator;

/**
 * A parsed mime message with optional mime parts depending on its type.
 * 
 * A mime message may have any number of mime parts, and each part may have any
 * number of sub-parts, etc...
 * 
 * A message is a specialized "mime part". Namely the message keeps hold of text
 * versus HTML parts (and associated streams for easy access), holds a stream
 * for the entire message and all its parts, and maintains parts and their
 * relationships.
 *
 * @author Zaahid Bateson
 */
class Message extends MimePart
{
    /**
     * @var string unique ID used to identify the object to
     *      $this->partStreamRegistry when registering the stream.  The ID is
     *      used for opening stream parts with the mmp-mime-message "protocol".
     * 
     * @see \ZBateson\MailMimeParser\SimpleDi::registerStreamExtensions
     * @see \ZBateson\MailMimeParser\Stream\PartStream::stream_open
     */
    protected $objectId;
    
    /**
     * @var \ZBateson\MailMimeParser\MimePart The plain text part or null if
     *      there isn't one
     */
    protected $textPart;
    
    /**
     * @var \ZBateson\MailMimeParser\MimePart The HTML part stream or null if
     *      there isn't one
     */
    protected $htmlPart;
    
    /**
     * @var \ZBateson\MailMimeParser\MimePart[] array of parts in this message 
     */
    protected $parts = [];
    
    /**
     * Constructs a Message.
     * 
     * @param HeaderFactory $headerFactory
     */
    public function __construct(HeaderFactory $headerFactory)
    {
        parent::__construct($headerFactory);
        $this->objectId = uniqid();
    }
    
    /**
     * Returns the unique object ID registered with the PartStreamRegistry
     * service object.
     * 
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }
    
    /**
     * Returns true if the $part should be assigned as this message's main
     * text part content.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     * @return bool
     */
    private function isMessageTextPart(MimePart $part)
    {
        $type = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
        return ($type === 'text/plain' && empty($this->textPart));
    }
    
    /**
     * Returns true if the $part should be assigned as this message's main
     * html part content.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     * @return bool
     */
    private function isMessageHtmlPart(MimePart $part)
    {
        $type = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
        return ($type === 'text/html' && empty($this->htmlPart));
    }

    /**
     * Either adds the passed part to $this->textPart if its content type is
     * text/plain, to $this->htmlPart if it's text/html, or adds the part to the
     * parts array otherwise.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     */
    public function addPart(MimePart $part)
    {
        if ($this->isMessageTextPart($part)) {
            $this->textPart = $part;
        } elseif ($this->isMessageHtmlPart($part)) {
            $this->htmlPart = $part;
        } else {
            $this->parts[] = $part;
        }
    }
    
    /**
     * Returns the text part (or null if none is set.)
     * 
     * @return \ZBateson\MailMimeParser\MimePart
     */
    public function getTextPart()
    {
        return $this->textPart;
    }
    
    /**
     * Returns the HTML part (or null if none is set.)
     * 
     * @return \ZBateson\MailMimeParser\MimePart
     */
    public function getHtmlPart()
    {
        return $this->htmlPart;
    }
    
    /**
     * Returns the non-text, non-HTML part at the given 0-based index, or null
     * if none is set.
     * 
     * @param int $index
     * @return \ZBateson\MailMimeParser\MimePart
     */
    public function getAttachmentPart($index)
    {
        if (!isset($this->parts[$index])) {
            return null;
        }
        return $this->parts[$index];
    }
    
    /**
     * Returns all attachment parts.
     * 
     * @return \ZBateson\MailMimeParser\MimePart[]
     */
    public function getAllAttachmentParts()
    {
        return $this->parts;
    }
    
    /**
     * Returns the number of attachments available.
     * 
     * @return int
     */
    public function getAttachmentCount()
    {
        return count($this->parts);
    }
    
    /**
     * Returns a resource handle where the text content can be read.
     * 
     * @return resource
     */
    public function getTextStream()
    {
        if (!empty($this->textPart)) {
            return $this->textPart->getContentResourceHandle();
        }
        return null;
    }
    
    /**
     * Returns the text content as a string.
     * 
     * Reads the entire stream content into a string and returns it.  Returns
     * null if the message doesn't have a text part.
     * 
     * @return string
     */
    public function getTextContent()
    {
        $stream = $this->getTextStream();
        if ($stream === null) {
            return null;
        }
        return stream_get_contents($stream);
    }
    
    /**
     * Returns a resource handle where the HTML content can be read.
     * 
     * @return resource
     */
    public function getHtmlStream()
    {
        if (!empty($this->htmlPart)) {
            return $this->htmlPart->getContentResourceHandle();
        }
        return null;
    }
    
    /**
     * Returns the HTML content as a string.
     * 
     * Reads the entire stream content into a string and returns it.  Returns
     * null if the message doesn't have an HTML part.
     * 
     * @return string
     */
    public function getHtmlContent()
    {
        $stream = $this->getHtmlStream();
        if ($stream === null) {
            return null;
        }
        return stream_get_contents($stream);
    }
    
    /**
     * Returns true if either a Content-Type or Mime-Version header are defined
     * in this Message.
     * 
     * @return bool
     */
    public function isMime()
    {
        $contentType = $this->getHeaderValue('Content-Type');
        $mimeVersion = $this->getHeaderValue('Mime-Version');
        return ($contentType !== null || $mimeVersion !== null);
    }
    
    protected function saveParts($handle, Iterator $partsIter, $curParent)
    {
        while ($partsIter->valid()) {
            $part = $partsIter->current();
            $parent = $part->getParent();
            if ($parent !== $curParent && $curParent !== $part) {
                if ($curParent !== null && $parent === $curParent->getParent()) {
                    fwrite($handle, "\r\n--");
                    fwrite($handle, $curParent->getHeaderParameter('Content-Type', 'boundary'));
                    fwrite($handle, "--\r\n\r\n");
                } else {
                    $this->saveParts($handle, $partsIter, $parent);
                }
                return;
            }
            if ($curParent !== null && $curParent->getHeaderParameter('Content-Type', 'boundary') != null) {
                fwrite($handle, "\r\n--");
                fwrite($handle, $curParent->getHeaderParameter('Content-Type', 'boundary'));
                fwrite($handle, "\r\n");
            }
            if ($part !== $this) {
                $part->save($handle);
            } else {
                $part->saveContent($handle);
            }
            $partsIter->next();
        }
    }
    
    public function save($handle)
    {
        $this->saveHeaders($handle);
        $parts = $this->parts;
        if (!empty($this->textPart)) {
            array_unshift($parts, $this->textPart);
        }
        if (!empty($this->htmlPart)) {
            array_unshift($parts, $this->htmlPart);
        }
        $this->saveParts($handle, new ArrayIterator($parts), $this);
    }
}
