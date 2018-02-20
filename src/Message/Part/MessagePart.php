<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\MailMimeParser;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\StreamWrapper;

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
     * @var PartStreamFilterManager manages attached filters to $contentHandle
     */
    protected $partStreamFilterManager;
    
    /**
     * @var string can be used to set an override for content's charset in cases
     *      where a user wants to set a default other than ISO-8859-1.
     */
    protected $charsetOverride;

    protected $hasContentStream = false;

    /**
     * Sets up class dependencies.
     * 
     * @param resource $handle
     * @param PartBuilder $partBuilder
     * @param PartStreamFilterManager $partStreamFilterManager
     */
    public function __construct(
        $handle,
        PartBuilder $partBuilder,
        PartStreamFilterManager $partStreamFilterManager
    ) {
        $this->handle = $handle;
        if ($handle && $partBuilder->getStreamContentLength() !== 0) {
            $partStream = Psr7\stream_for($handle);
            $partLimitStream = new LimitStream($partStream, $partBuilder->getStreamContentLength(), $partBuilder->getStreamContentStartOffset());
            $partStreamFilterManager->setHandle(
                StreamWrapper::getResource($partLimitStream)
            );
            $this->hasContentStream = true;
        }
        $this->partStreamFilterManager = $partStreamFilterManager;
    }

    /**
     * Closes the attached resource handles.
     */
    public function __destruct()
    {
        // stream_filter_append may be cleaned up by PHP, but for large files
        // and many emails may be more efficient to fully clean up
        //$this->partStreamFilterManager->closeHandle();
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Returns true if there's a content stream associated with the part.
     *
     * @return boolean
     */
    public function hasContent()
    {
        return $this->hasContentStream;
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
     * Overrides the default character set used for reading content from content
     * streams in cases where a user knows the source charset is not what is
     * specified.
     * 
     * If set, the returned value from MessagePart::getCharset is ignored.
     * 
     * Note that setting an override on a Message and calling getTextStream,
     * getTextContent, getHtmlStream or getHtmlContent will not be applied to
     * those sub-parts, unless the text/html part is the Message itself.
     * Instead, Message:getTextPart() should be called, and setCharsetOverride
     * called on the returned MessagePart.
     * 
     * @param string $charsetOverride
     * @param boolean $onlyIfNoCharset if true, $charsetOverride is used only if
     *        getCharset returns null.
     */
    public function setCharsetOverride($charsetOverride, $onlyIfNoCharset = false)
    {
        if (!$onlyIfNoCharset || $this->getCharset() === null) {
            $this->charsetOverride = $charsetOverride;
        }
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
     * Content-Transfer-Encoding and Content-Type headers unless a charset
     * override is set.  The following transfer encodings are supported:
     *
     * - quoted-printable
     * - base64
     * - x-uuencode
     *
     * In addition a ZBateson\MailMimeParser\Stream\CharsetStreamFilter is
     * attached for text parts to convert from the content's charset to the
     * target charset passed in as an argument (defaults to UTF-8).
     *
     * @param string $charset
     * @return resource
     */
    public function getContentResourceHandle($charset = MailMimeParser::DEFAULT_CHARSET)
    {
        if ($this->hasContent()) {
            $tr = $this->getContentTransferEncoding();
            $ch = ($this->charsetOverride !== null) ? $this->charsetOverride : $this->getCharset();
            return $this->partStreamFilterManager->getContentHandle(
                $tr,
                $ch,
                $charset
            );
        }
        return null;
    }

    /**
     * Shortcut to reading stream content and assigning it to a string.  Returns
     * null if the part doesn't have a content stream.
     * 
     * The returned string is encoded to the passed $charset character encoding,
     * defaulting to UTF-8.
     *
     * @return string
     */
    public function getContent($charset = MailMimeParser::DEFAULT_CHARSET)
    {
        if ($this->hasContent()) {
            $handle = $this->getContentResourceHandle($charset);
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
