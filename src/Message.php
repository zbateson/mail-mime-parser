<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;

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
     * @see \ZBateson\MailMimeParser\PartStream::stream_open
     */
    protected $objectId;
    
    /**
     * @var resource The plain text part stream (if any)
     */
    protected $textPart;
    
    /**
     * @var resource The HTML part stream (if any)
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
     * Either adds the passed part to $this->textPart if its content type is
     * text/plain, to $this->htmlPart if it's text/html, or adds the part to the
     * parts array otherwise.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     */
    public function addPart(MimePart $part)
    {
        $type = $part->getHeaderValue('Content-Type');
        if ((empty($type) || strtolower($type) === 'text/plain') && empty($this->textPart)) {
            $this->textPart = $part;
        } elseif (strtolower($type) === 'text/html' && empty($this->htmlPart)) {
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
}
