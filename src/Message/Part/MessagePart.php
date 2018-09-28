<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;
use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Represents a single part of a message.
 *
 * A MessagePart object may have any number of child parts, or may be a child
 * itself with its own parent or parents.
 *
 * @author Zaahid Bateson
 */
abstract class MessagePart
{
    /**
     * @var PartStreamFilterManager manages attached filters to $contentHandle
     */
    protected $partStreamFilterManager;

    /**
     * @var StreamFactory for creating MessagePartStream objects
     */
    protected $streamFactory;

    /**
     * @var ParentPart parent part
     */
    protected $parent;

    /**
     * @var StreamInterface a Psr7 stream containing this part's headers,
     *      content and children
     */
    protected $stream;

    /**
     * @var StreamInterface a Psr7 stream containing this part's content
     */
    protected $contentStream;
    
    /**
     * @var string can be used to set an override for content's charset in cases
     *      where a user wants to set a default other than ISO-8859-1.
     */
    protected $charsetOverride;

    /**
     * @var boolean set to true when a user attaches a stream manually, it's
     *      assumed to already be decoded or to have relevant transfer encoding
     *      decorators attached already.
     */
    protected $ignoreTransferEncoding;

    /**
     * Constructor
     * 
     * @param PartStreamFilterManager $partStreamFilterManager
     * @param StreamFactory $streamFactory
     * @param StreamInterface $stream
     * @param StreamInterface $contentStream
     */
    public function __construct(
        PartStreamFilterManager $partStreamFilterManager,
        StreamFactory $streamFactory,
        StreamInterface $stream = null,
        StreamInterface $contentStream = null
    ) {
        $this->partStreamFilterManager = $partStreamFilterManager;
        $this->streamFactory = $streamFactory;

        $this->stream = $stream;
        $this->contentStream = $contentStream;
        if ($contentStream !== null) {
            $partStreamFilterManager->setStream(
                $contentStream
            );
        }
    }

    /**
     * Overridden to close streams.
     */
    public function __destruct()
    {
        if ($this->stream !== null) {
            $this->stream->close();
        }
        if ($this->contentStream !== null) {
            $this->contentStream->close();
        }
    }

    /**
     * Called when operations change the content of the MessagePart.
     *
     * The function causes calls to getStream() to return a dynamic
     * MessagePartStream instead of the read stream for this MessagePart and all
     * parent MessageParts.
     */
    protected function onChange()
    {
        $this->markAsChanged();
        if ($this->parent !== null) {
            $this->parent->onChange();
        }
    }

    /**
     * Marks the part as changed, forcing the part to be rewritten when saved.
     *
     * Normal operations to a MessagePart automatically mark the part as
     * changed and markAsChanged() doesn't need to be called in those cases.
     *
     * The function can be called to indicate an external change that requires
     * rewriting this part, for instance changing a message from a non-mime
     * message to a mime one, would require rewriting non-mime children to
     * insure suitable headers are written.
     *
     * Internally, the function discards the part's stream, forcing a stream to
     * be created when calling getStream().
     */
    public function markAsChanged()
    {
        // the stream is not closed because $this->contentStream may still be
        // attached to it.  GuzzleHttp will clean it up when destroyed.
        $this->stream = null;
    }

    /**
     * Returns true if there's a content stream associated with the part.
     *
     * @return boolean
     */
    public function hasContent()
    {
        return ($this->contentStream !== null);
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
     * Returns the Content ID of the part, or null if not defined.
     *
     * @return string|null
     */
    public abstract function getContentId();
    
    /**
     * Returns a resource handle containing this part, including any headers for
     * a MimePart, its content, and all its children.
     * 
     * @return resource the resource handle
     */
    public function getResourceHandle()
    {
        return StreamWrapper::getResource($this->getStream());
    }

    /**
     * Returns a Psr7 StreamInterface containing this part, including any
     * headers for a MimePart, its content, and all its children.
     *
     * @return StreamInterface the resource handle
     */
    public function getStream()
    {
        if ($this->stream === null) {
            return $this->streamFactory->newMessagePartStream($this);
        }
        $this->stream->rewind();
        return $this->stream;
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
     * Returns a new resource stream handle for the part's content or null if
     * the part doesn't have a content section.
     *
     * The returned resource handle is a resource stream with decoding filters
     * appended to it.  The attached filters are determined by looking at the
     * part's Content-Transfer-Encoding and Content-Type headers unless a
     * charset override is set.  The following transfer encodings are supported:
     *
     * - quoted-printable
     * - base64
     * - x-uuencode
     *
     * In addition, the charset of the underlying stream is converted to the
     * passed $charset if the content is known to be text.
     *
     * @param string $charset
     * @return resource
     */
    public function getContentResourceHandle($charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $stream = $this->getContentStream($charset);
        if ($stream !== null) {
            return StreamWrapper::getResource($stream);
        }
        return null;
    }

    /**
     * Returns the StreamInterface for the part's content or null if the part
     * doesn't have a content section.
     *
     * Because the returned stream may be a shared object if called multiple
     * times, the function isn't exposed publicly.  If called multiple times
     * with the same $charset, and the value of the part's
     * Content-Transfer-Encoding header not having changed, the returned stream
     * is the same instance and may need to be rewound.
     *
     * Note that PartStreamFilterManager rewinds the stream before returning it.
     *
     * @param string $charset
     * @return StreamInterface
     */
    public function getContentStream($charset = MailMimeParser::DEFAULT_CHARSET)
    {
        if ($this->hasContent()) {
            $tr = ($this->ignoreTransferEncoding) ? '' : $this->getContentTransferEncoding();
            $ch = ($this->charsetOverride !== null) ? $this->charsetOverride : $this->getCharset();
            return $this->partStreamFilterManager->getContentStream(
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
     * @param string $charset
     * @return string
     */
    public function getContent($charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $stream = $this->getContentStream($charset);
        if ($stream !== null) {
            return $stream->getContents();
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

    /**
     * Attaches the stream or resource handle for the part's content.  The
     * stream is closed when another stream is attached, or the MimePart is
     * destroyed.
     *
     * @param StreamInterface $stream
     * @param string $streamCharset
     */
    public function attachContentStream(StreamInterface $stream, $streamCharset = MailMimeParser::DEFAULT_CHARSET)
    {
        if ($this->contentStream !== null && $this->contentStream !== $stream) {
            $this->contentStream->close();
        }
        $this->contentStream = $stream;
        $ch = ($this->charsetOverride !== null) ? $this->charsetOverride : $this->getCharset();
        if ($ch !== null && $streamCharset !== $ch) {
            $this->charsetOverride = $streamCharset;
        }
        $this->ignoreTransferEncoding = true;
        $this->partStreamFilterManager->setStream($stream);
        $this->onChange();
    }

    /**
     * Detaches and closes the content stream.
     */
    public function detachContentStream()
    {
        $this->contentStream = null;
        $this->partStreamFilterManager->setStream(null);
        $this->onChange();
    }

    /**
     * Sets the content of the part to the passed resource.
     *
     * @param string|resource|StreamInterface $resource
     * @param string $charset
     */
    public function setContent($resource, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $stream = Psr7\stream_for($resource);
        $this->attachContentStream($stream, $charset);
        // this->onChange called in attachContentStream
    }

    /**
     * Saves the message/part as to the passed resource handle.
     *
     * @param resource|StreamInterface $streamOrHandle
     */
    public function save($streamOrHandle)
    {
        $message = $this->getStream();
        $message->rewind();
        $stream = Psr7\stream_for($streamOrHandle);
        Psr7\copy_to_stream($message, $stream);
        // don't close when out of scope
        $stream->detach();
    }

    /**
     * Returns the message/part as a string.
     *
     * Convenience method for calling getStream()->getContents().
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getStream()->getContents();
    }
}
