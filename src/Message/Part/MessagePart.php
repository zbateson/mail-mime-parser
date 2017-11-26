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
     * @var string url for opening a content handle with partStreamFilterManager
     */
    protected $contentUrl;
    
    /**
     * @var PartStreamFilterManager manages attached filters to $contentHandle
     */
    protected $partStreamFilterManager;
    
    /**
     * @var string a unique ID representing the message this part belongs to.
     */
    protected $messageObjectId;

    /**
     * Sets up class dependencies.
     * 
     * @param string $messageObjectId
     * @param PartBuilder $partBuilder
     * @param PartStreamFilterManager $partStreamFilterManager
     */
    public function __construct(
        $messageObjectId,
        PartBuilder $partBuilder,
        PartStreamFilterManager $partStreamFilterManager
    ) {
        $this->messageObjectId = $messageObjectId;
        $partUrl = $partBuilder->getStreamPartUrl($messageObjectId);
        $this->contentUrl = $partBuilder->getStreamContentUrl($messageObjectId);
        $partStreamFilterManager->setContentUrl($this->contentUrl);
        $this->partStreamFilterManager = $partStreamFilterManager;
        if ($partUrl !== null) {
            $this->handle = fopen($partUrl, 'r');
        }
    }

    /**
     * Closes the attached resource handles.
     */
    public function __destruct()
    {
        // stream_filter_append may be cleaned up by PHP, but for large files
        // and many emails may be more efficient to fully clean up
        $this->partStreamFilterManager->setContentUrl(null);
        if (is_resource($this->handle)) {
            fclose($this->handle);
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
        if ($this->contentUrl !== null) {
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
     * Returns the charset of the content, or null if not applicable/defined.
     * 
     * @return string
     */
    public abstract function getCharset();
    
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
     * Returns a filename for the part if one is defined, or null otherwise.
     * 
     * @return string
     */
    public function getFilename()
    {
        return null;
    }
    
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
     * Note that 'rewind()' is called on the resource prior to returning it,
     * which may affect other read operations if multiple calls to 'getHandle'
     * are used.
     * 
     * The resource stream is handled by MessagePart and is closed by the
     * destructor.
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
     * to it.  The attached filters are determined by the passed
     * $transferEncoding and $charset headers, or by looking at the part's
     * Content-Transfer-Encoding and Content-Type headers if not passed.  The
     * following encodings are currently supported:
     *
     * - quoted-printable
     * - base64
     * - x-uuencode
     *
     * In addition a ZBateson\MailMimeParser\Stream\CharsetStreamFilter is
     * attached for text parts to convert text in the stream to UTF-8.
     *
     * @param string $transferEncoding
     * @param string $charset
     * @return resource
     */
    public function getContentResourceHandle($transferEncoding = null, $charset = null)
    {
        if ($this->contentUrl !== null) {
            $tr = $transferEncoding;
            $ch = $charset;
            if (empty($tr)) {
                $tr = $this->getContentTransferEncoding();
            }
            if (empty($ch)) {
                $ch = $this->getCharset();
            }
            return $this->partStreamFilterManager->getContentHandle(
                $tr,
                $ch,
                'UTF-8'
            );
        }
        return null;
    }

    /**
     * Shortcut to reading stream content and assigning it to a string.  Returns
     * null if the part doesn't have a content stream.
     *
     * @return string
     */
    public function getContent($transferEncoding = null, $charset = null)
    {
        if ($this->hasContent()) {
            $handle = $this->getContentResourceHandle($transferEncoding, $charset);
            $pos = ftell($handle);
            rewind($handle);
            $text = stream_get_contents($handle);
            fseek($handle, $pos);
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
