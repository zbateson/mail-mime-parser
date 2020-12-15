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
use ZBateson\MailMimeParser\Message\Part\PartBuilder;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Implements IMessagePart with a few concrete methods that can have reasonable
 * defaults.
 *
 * @author Zaahid Bateson
 */
abstract class MessagePart implements IMessagePart
{
    /**
     * @var StreamFactory for creating MessagePartStream objects
     */
    protected $streamFactory;

    /**
     * @var ParentPart parent part
     */
    protected $parent;

    /**
     * @var PartStreamContainer holds 'stream' and 'contentStream'.
     */
    protected $partStreamContainer;

    /**
     * @var string can be used to set an override for content's charset in cases
     *      where a user knows the charset on the content is not what it claims
     *      to be.
     */
    protected $charsetOverride;

    /**
     * @var bool set to true when a user attaches a stream manually, it's
     *      assumed to already be decoded or to have relevant transfer encoding
     *      decorators attached already.
     */
    protected $ignoreTransferEncoding;

    /**
     * @var bool set to true if the part's been changed, and the attached stream
     *      in $partStreamContainer would no longer represent the actual content
     *      of the part, and a MessagePartStream should be used instead.
     */
    protected $streamChanged;

    /**
     * Constructor
     *
     * @param StreamFactory $streamFactory
     */
    public function __construct(StreamFactory $streamFactory)
    {
        $this->streamFactory = $streamFactory;
        $this->streamChanged = true;
    }

    /**
     *
     * @param PartBuilder $partBuilder
     */
    public function initFrom(PartBuilder $partBuilder, PartStreamContainer $container)
    {
        $this->streamChanged = ($container->getStream() === null);
        $this->partStreamContainer = $container;
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
     * Internally, once a part has been marked as changed, the underlying stream
     * used at creation is no longer used, and instead a MessagePartStream
     * object is created which generates the stream dynamically based on the
     * part.
     */
    public function markAsChanged()
    {
        $this->streamChanged = true;
    }

    /**
     * Returns true if there's a content stream associated with the part.
     *
     * @return boolean
     */
    public function hasContent()
    {
        return $this->partStreamContainer->hasContent();
    }

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
     * {@inheritDoc}.
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
        if ($this->streamChanged) {
            return $this->streamFactory->newMessagePartStream($this);
        }
        $stream = $this->partStreamContainer->getStream();
        $stream->rewind();
        return $stream;
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
     * Returns a resource handle for the content's stream, or null if the part
     * doesn't have a content stream.
     *
     * The method wraps a call to {@see MessagePart::getContentStream()} and
     * returns a resource handle for the returned Stream.
     *
     * Note: this method should *not* be used and has been deprecated. Instead,
     * use Psr7 streams with getContentStream.  Multibyte chars will not be read
     * correctly with fread.
     *
     * @param string $charset
     * @deprecated since version 1.2.1
     * @return resource|null
     */
    public function getContentResourceHandle($charset = MailMimeParser::DEFAULT_CHARSET)
    {
        trigger_error("getContentResourceHandle is deprecated since version 1.2.1", E_USER_DEPRECATED);
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
     * The library automatically handles decoding and charset conversion (to the
     * target passed $charset) based on the part's transfer encoding as returned
     * by {@see MessagePart::getContentTransferEncoding()} and the part's
     * charset as returned by {@see MessagePart::getCharset()}.  The returned
     * stream is ready to be read from directly.
     *
     * Note that the returned Stream is a shared object.  If called multiple
     * time with the same $charset, and the value of the part's
     * Content-Transfer-Encoding header not having changed, the stream will be
     * rewound.  This would affect other existing variables referencing the
     * stream, for example:
     *
     * ```
     * // assuming $part is a part containing the following
     * // string for its content: '12345678'
     * $stream = $part->getContentStream();
     * $someChars = $part->read(4);
     *
     * $stream2 = $part->getContentStream();
     * $moreChars = $part->read(4);
     * echo ($someChars === $moreChars);    //1
     * ```
     *
     * In this case the Stream was rewound, and $stream's second call to read 4
     * bytes reads the same first 4.
     *
     * @param string $charset
     * @return StreamInterface
     */
    public function getContentStream($charset = MailMimeParser::DEFAULT_CHARSET)
    {
        if ($this->hasContent()) {
            $tr = ($this->ignoreTransferEncoding) ? '' : $this->getContentTransferEncoding();
            $ch = ($this->charsetOverride !== null) ? $this->charsetOverride : $this->getCharset();
            return $this->partStreamContainer->getContentStream(
                $tr,
                $ch,
                $charset
            );
        }
        return null;
    }

    /**
     * Returns the raw data stream for the current part, if it exists, or null
     * if there's no content associated with the stream.
     *
     * This is basically the same as calling
     * {@see MessagePart::getContentStream()}, except no automatic charset
     * conversion is done.  Note that for non-text streams, this doesn't have an
     * effect, as charset conversion is not performed in that case, and is
     * useful only when:
     *
     * - The charset defined is not correct, and the conversion produces errors;
     *   or
     * - You'd like to read the raw contents without conversion, for instance to
     *   save it to file or allow a user to download it as-is (in a download
     *   link for example).
     *
     * @param string $charset
     * @return StreamInterface
     */
    public function getBinaryContentStream()
    {
        if ($this->hasContent()) {
            $tr = ($this->ignoreTransferEncoding) ? '' : $this->getContentTransferEncoding();
            return $this->partStreamContainer->getBinaryContentStream($tr);
        }
        return null;
    }

    /**
     * Returns a resource handle for the content's raw data stream, or null if
     * the part doesn't have a content stream.
     *
     * The method wraps a call to {@see MessagePart::getBinaryContentStream()}
     * and returns a resource handle for the returned Stream.
     *
     * @return resource|null
     */
    public function getBinaryContentResourceHandle()
    {
        $stream = $this->getBinaryContentStream();
        if ($stream !== null) {
            return StreamWrapper::getResource($stream);
        }
        return null;
    }

    /**
     * Saves the binary content of the stream to the passed file, resource or
     * stream.
     *
     * Note that charset conversion is not performed in this case, and the
     * contents of the part are saved in their binary format as transmitted (but
     * after any content-transfer decoding is performed).  {@see
     * MessagePart::getBinaryContentStream()} for a more detailed description of
     * the stream.
     *
     * If the passed parameter is a string, it's assumed to be a filename to
     * write to.  The file is opened in 'w+' mode, and closed before returning.
     *
     * When passing a resource or Psr7 Stream, the resource is not closed, nor
     * rewound.
     *
     * @param string|resource|Stream $filenameResourceOrStream
     */
    public function saveContent($filenameResourceOrStream)
    {
        $resourceOrStream = $filenameResourceOrStream;
        if (is_string($filenameResourceOrStream)) {
            $resourceOrStream = fopen($filenameResourceOrStream, 'w+');
        }

        $stream = Psr7\stream_for($resourceOrStream);
        Psr7\copy_to_stream($this->getBinaryContentStream(), $stream);

        if (!is_string($filenameResourceOrStream)
            && !($filenameResourceOrStream instanceof StreamInterface)) {
            // only detach if it wasn't a string or StreamInterface, so the
            // fopen call can be properly closed if it was
            $stream->detach();
        }
    }

    /**
     * Shortcut to reading stream content and assigning it to a string.  Returns
     * null if the part doesn't have a content stream.
     *
     * The returned string is encoded to the passed $charset character encoding,
     * defaulting to UTF-8.
     *
     * @see MessagePart::getContentStream()
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
        $ch = ($this->charsetOverride !== null) ? $this->charsetOverride : $this->getCharset();
        if ($ch !== null && $streamCharset !== $ch) {
            $this->charsetOverride = $streamCharset;
        }
        $this->ignoreTransferEncoding = true;
        $this->partStreamContainer->setContentStream($stream);
        $this->onChange();
    }

    /**
     * Detaches the content stream.
     */
    public function detachContentStream()
    {
        $this->partStreamContainer->setContentStream(null);
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
     * Saves the message/part to the passed file, resource, or stream.
     *
     * If the passed parameter is a string, it's assumed to be a filename to
     * write to.  The file is opened in 'w+' mode, and closed before returning.
     *
     * When passing a resource or Psr7 Stream, the resource is not closed, nor
     * rewound.
     *
     * @param string|resource|StreamInterface $filenameResourceOrStream
     */
    public function save($filenameResourceOrStream)
    {
        $resourceOrStream = $filenameResourceOrStream;
        if (is_string($filenameResourceOrStream)) {
            $resourceOrStream = fopen($filenameResourceOrStream, 'w+');
        }

        $partStream = $this->getStream();
        $partStream->rewind();
        $stream = Psr7\stream_for($resourceOrStream);
        Psr7\copy_to_stream($partStream, $stream);

        if (!is_string($filenameResourceOrStream)
            && !($filenameResourceOrStream instanceof StreamInterface)) {
            // only detach if it wasn't a string or StreamInterface, so the
            // fopen call can be properly closed if it was
            $stream->detach();
        }
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
