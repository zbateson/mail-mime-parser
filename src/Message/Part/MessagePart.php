<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

/**
 * Represents a single part of a message.
 *
 * A MessagePart object may have any number of child parts, or may be a child
 * itself with its own parent or parents.
 *
 * The content of the part can be read from its PartStream resource handle,
 * accessible via MimePart::getContentResourceHanlde.
 *
 * @author Zaahid Bateson
 */
abstract class MessagePart
{
    /**
     * @var \ZBateson\MailMimeParser\Message\Part\MimePart parent part
     */
    protected $parent;

    /**
     * @var resource a resource handle containing this part's headers, content
     *      and children
     */
    protected $handle;
    
    /**
     * @var resource a resource handle to this part's content
     */
    protected $contentHandle;
    
    /**
     * @var string a unique ID representing the message this part belongs to.
     */
    protected $messageObjectId;

    /**
     * Sets up class dependencies.
     * 
     * @param string $messageObjectId
     * @param PartBuilder $partBuilder
     */
    public function __construct($messageObjectId, PartBuilder $partBuilder)
    {
        $this->messageObjectId = $messageObjectId;
        $partFilename = $partBuilder->getStreamPartFilename($messageObjectId);
        $contentFilename = $partBuilder->getStreamContentFilename($messageObjectId);
        if ($partFilename !== null) {
            $this->handle = fopen($partFilename, 'r');
        }
        if ($contentFilename !== null) {
            $this->contentHandle = fopen($contentFilename, 'r');
        }
    }

    /**
     * Closes the attached resource handles.
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
        if (is_resource($this->contentHandle)) {
            fclose($this->contentHandle);
        }
    }
    
    /**
     * Returns the unique object ID registered with the PartStreamRegistry
     * service object for the message this part belongs to.
     * 
     * @return string
     * @see \ZBateson\MailMimeParser\SimpleDi::registerStreamExtensions
     * @see \ZBateson\MailMimeParser\Stream\PartStream::stream_open
     */
    public function getMessageObjectId()
    {
        return $this->messageObjectId;
    }

    /**
     * Returns true if there's a content stream associated with the part.
     *
     * @return boolean
     */
    public function hasContent()
    {
        if ($this->contentHandle !== null) {
            return true;
        }
        return false;
    }

    /**
     * Returns true if this part's mime type is text/plain, text/html or has a
     * text/* and has a defined 'charset' attribute.
     * 
     * @return bool
     */
    public abstract function isTextPart();
    
    /**
     * Returns the mime type of the content.
     * 
     * @return string
     */
    public abstract function getContentType();
    
    /**
     * Returns the content's disposition.
     * 
     * @return string
     */
    public abstract function getContentDisposition();
    
    /**
     * Returns the content-transfer-encoding used for this part.
     * 
     * @return string
     */
    public abstract function getContentTransferEncoding();
    
    /**
     * Returns true if the current part is a mime part.
     * 
     * @return bool
     */
    public abstract function isMime();
    
    /**
     * Returns a resource stream handle allowing a user to read the original
     * stream (including headers and child parts) that was used to create the
     * current part.
     * 
     * The part contains an original stream handle only if it was explicitly set
     * by a call to MimePart::attachOriginalStreamHandle.  MailMimeParser only
     * sets this during the parsing phase in MessageParser, and is not otherwise
     * changed or updated.  New parts added below this part, changed headers,
     * etc... would not be reflected in the returned stream handle.
     * 
     * This method was added mainly for signature verification in
     * Message::getOriginalMessageStringForSignatureVerification
     * 
     * @return resource the resource handle or null if not set
     */
    public function getHandle()
    {
        if (is_resource($this->handle)) {
            rewind($this->handle);
        }
        return $this->handle;
    }

    /**
     * Returns the resource stream handle for the part's content or null if not
     * set.  rewind() is called on the stream before returning it.
     *
     * The resource is automatically closed by MimePart's destructor and should
     * not be closed otherwise.
     *
     * The returned resource handle is a stream with decoding filters appended
     * to it.  The attached filters are determined by looking at the part's
     * Content-Encoding header.  The following encodings are currently
     * supported:
     *
     * - Quoted-Printable
     * - Base64
     * - X-UUEncode
     *
     * UUEncode may be automatically attached for a message without a defined
     * Content-Encoding and Content-Type if it has a UUEncoded part to support
     * older non-mime message attachments.
     *
     * In addition, character encoding for text streams is converted to UTF-8
     * if {@link \ZBateson\MailMimeParser\Message\Part\MimePart::isTextPart
     * MimePart::isTextPart} returns true.
     *
     * @return resource
     */
    public function getContentResourceHandle()
    {
        if (is_resource($this->contentHandle)) {
            rewind($this->contentHandle);
        }
        return $this->contentHandle;
    }

    /**
     * Shortcut to reading stream content and assigning it to a string.  Returns
     * null if the part doesn't have a content stream.
     *
     * @return string
     */
    public function getContent()
    {
        if ($this->hasContent()) {
            $text = stream_get_contents($this->contentHandle);
            rewind($this->contentHandle);
            return $text;
        }
        return null;
    }

    /**
     * Returns this part's parent.
     *
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function getParent()
    {
        return $this->parent;
    }
}
