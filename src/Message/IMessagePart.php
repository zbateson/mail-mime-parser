<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\MailMimeParser;
use Psr\Http\Message\StreamInterface;
use SplSubject;

/**
 * Represents a single part of a message.
 *
 * A MessagePart object may have any number of child parts, or may be a child
 * itself with its own parent or parents.
 *
 * @author Zaahid Bateson
 */
interface IMessagePart extends SplSubject
{
    /**
     * Returns true if the part contains a 'body' (content).
     *
     * @return boolean
     */
    public function hasContent();

    /**
     * Returns true if this part's mime type is text/plain, text/html or has a
     * text/* and has a defined 'charset' attribute.
     *
     * @return bool
     */
    public function isTextPart();

    /**
     * Returns the mime type of the content.
     *
     * @return string
     */
    public function getContentType();

    /**
     * Returns the charset of the content, or null if not applicable/defined.
     *
     * @return string
     */
    public function getCharset();

    /**
     * Returns the content's disposition.
     *
     * @return string
     */
    public function getContentDisposition();

    /**
     * Returns the content-transfer-encoding used for this part.
     *
     * @return string
     */
    public function getContentTransferEncoding();

    /**
     * Returns a filename for the part if one is defined, or null otherwise.
     *
     * @return string
     */
    public function getFilename();

    /**
     * Returns true if the current part is a mime part.
     *
     * @return bool
     */
    public function isMime();

    /**
     * Returns the Content ID of the part, or null if not defined.
     *
     * @return string|null
     */
    public function getContentId();

    /**
     * Returns a resource handle containing this part, including any headers for
     * a MimePart, its content, and all its children.
     *
     * @return resource the resource handle
     */
    public function getResourceHandle();

    /**
     * Returns a Psr7 StreamInterface containing this part, including any
     * headers for a MimePart, its content, and all its children.
     *
     * @return StreamInterface the resource handle
     */
    public function getStream();

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
    public function setCharsetOverride($charsetOverride, $onlyIfNoCharset = false);

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
    public function getContentResourceHandle($charset = MailMimeParser::DEFAULT_CHARSET);

    /**
     * Returns the StreamInterface for the part's content or null if the part
     * doesn't have a content section.
     *
     * The library automatically handles decoding and charset conversion (to the
     * target passed $charset) based on the part's transfer encoding as returned
     * by {@see IMessagePart::getContentTransferEncoding()} and the part's
     * charset as returned by {@see IMessagePart::getCharset()}.  The returned
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
    public function getContentStream($charset = MailMimeParser::DEFAULT_CHARSET);

    /**
     * Returns the raw data stream for the current part, if it exists, or null
     * if there's no content associated with the stream.
     *
     * This is basically the same as calling
     * {@see IMessagePart::getContentStream()}, except no automatic charset
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
    public function getBinaryContentStream();

    /**
     * Returns a resource handle for the content's raw data stream, or null if
     * the part doesn't have a content stream.
     *
     * The method wraps a call to {@see IMessagePart::getBinaryContentStream()}
     * and returns a resource handle for the returned Stream.
     *
     * @return resource|null
     */
    public function getBinaryContentResourceHandle();

    /**
     * Saves the binary content of the stream to the passed file, resource or
     * stream.
     *
     * Note that charset conversion is not performed in this case, and the
     * contents of the part are saved in their binary format as transmitted (but
     * after any content-transfer decoding is performed).  {@see
     * IMessagePart::getBinaryContentStream()} for a more detailed description
     * of the stream.
     *
     * If the passed parameter is a string, it's assumed to be a filename to
     * write to.  The file is opened in 'w+' mode, and closed before returning.
     *
     * When passing a resource or Psr7 Stream, the resource is not closed, nor
     * rewound.
     *
     * @param string|resource|Stream $filenameResourceOrStream
     */
    public function saveContent($filenameResourceOrStream);

    /**
     * Shortcut to reading stream content and assigning it to a string.  Returns
     * null if the part doesn't have a content stream.
     *
     * The returned string is encoded to the passed $charset character encoding,
     * defaulting to UTF-8.
     *
     * @see IMessagePart::getContentStream()
     * @param string $charset
     * @return string
     */
    public function getContent($charset = MailMimeParser::DEFAULT_CHARSET);

    /**
     * Returns this part's parent.
     *
     * @return IMimePart
     */
    public function getParent();

    /**
     * Attaches the stream or resource handle for the part's content.  The
     * stream is closed when another stream is attached, or the MimePart is
     * destroyed.
     *
     * @param StreamInterface $stream
     * @param string $streamCharset
     */
    public function attachContentStream(StreamInterface $stream, $streamCharset = MailMimeParser::DEFAULT_CHARSET);

    /**
     * Detaches the content stream.
     */
    public function detachContentStream();

    /**
     * Sets the content of the part to the passed resource.
     *
     * @param string|resource|StreamInterface $resource
     * @param string $charset
     */
    public function setContent($resource, $charset = MailMimeParser::DEFAULT_CHARSET);

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
    public function save($filenameResourceOrStream);

    /**
     * Returns the message/part as a string.
     *
     * Convenience method for calling getStream()->getContents().
     *
     * @return string
     */
    public function __toString();
}
