<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\ParameterHeader;
use ZBateson\MailMimeParser\Stream\StreamLeftover;

/**
 * Represents a single part of a multi-part mime message.
 *
 * A MimePart object may have any number of child parts, or may be a child
 * itself with its own parent or parents.
 *
 * The content of the part can be read from its PartStream resource handle,
 * accessible via MimePart::getContentResourceHanlde.
 *
 * @author Zaahid Bateson
 */
class MimePart
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      object used for created headers
     */
    protected $headerFactory;

    /**
     * @var \ZBateson\MailMimeParser\Header\AbstractHeader[] array of header
     * objects
     */
    protected $headers;

    /**
     * @var \ZBateson\MailMimeParser\MimePart parent part
     */
    protected $parent;

    /**
     * @var resource the content's resource handle
     */
    protected $handle;

    /**
     * @var \ZBateson\MailMimeParser\MimePart[] array of parts in this message
     */
    protected $parts = [];

    /**
     * @var \ZBateson\MailMimeParser\MimePart[] Maps mime types to parts for
     * looking up in getPartByMimeType
     */
    protected $mimeToPart = [];

    /**
     * Sets up class dependencies.
     *
     * @param HeaderFactory $headerFactory
     */
    public function __construct(HeaderFactory $headerFactory)
    {
        $this->headerFactory = $headerFactory;
    }

    /**
     * Closes the attached resource handle.
     */
    public function __destruct()
    {
        if ($this->handle !== null) {
            fclose($this->handle);
        }
    }

    /**
     * Adds the passed part to the parts array, and registers non-attachment/
     * non-multipart parts by their content type.
     *
     * @param \ZBateson\MailMimeParser\MimePart $part
     */
    public function addPart(MimePart $part)
    {
        $this->parts[] = $part;
        if ($part->getHeaderValue('Content-Disposition') === null && !$part->isMultiPart()) {
            $key = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
            $this->mimeToPart[$key] = $part;
        }
    }

    /**
     * Unregisters the child part from this part.
     *
     * @param \ZBateson\MailMimeParser\MimePart $part
     */
    public function removePart(MimePart $part)
    {
        $partsArray = [];
        foreach ($this->parts as $apart) {
            if ($apart !== $part) {
                $partsArray[] = $apart;
            }
        }
        $key = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
        unset($this->mimeToPart[$key]);
        $this->parts = $partsArray;
    }

    /**
     * Returns the non-text, non-HTML part at the given 0-based index, or null
     * if none is set.
     *
     * @param int $index
     * @return \ZBateson\MailMimeParser\MimePart
     */
    public function getPart($index)
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
    public function getAllParts()
    {
        return $this->parts;
    }

    /**
     * Returns the number of attachments available.
     *
     * @return int
     */
    public function getPartCount()
    {
        return count($this->parts);
    }

    /**
     * Returns the part associated with the passed mime type if it exists.
     *
     * @param string $mimeType
     * @return \ZBateson\MailMimeParser\MimePart or null
     */
    public function getPartByMimeType($mimeType)
    {
        $key = strtolower($mimeType);
        if (isset($this->mimeToPart[$key])) {
            return $this->mimeToPart[$key];
        }
        return null;
    }

    /**
     * Returns true if there's a content stream associated with the part.
     *
     * @return boolean
     */
    public function hasContent()
    {
        if (!empty($this->handle)) {
            return true;
        }
        return false;
    }

    /**
     * Returns true if this part's mime type is multipart/*
     *
     * @return bool
     */
    public function isMultiPart()
    {
        return preg_match(
            '~multipart/\w+~i',
            $this->getHeaderValue('Content-Type', 'text/plain')
        );
    }
    
    /**
     * Returns true if this part's mime type is text/*
     * 
     * @return bool
     */
    public function isTextPart()
    {
        return preg_match(
            '~text/\w+~i',
            $this->getHeaderValue('Content-Type', 'text/plain')
        );
    }

    /**
     * Attaches the resource handle for the part's content.  The attached handle
     * is closed when the MimePart object is destroyed.
     *
     * @param resource $contentHandle
     */
    public function attachContentResourceHandle($contentHandle)
    {
        if ($this->handle !== null && $this->handle !== $contentHandle) {
            fclose($this->handle);
        }
        $this->handle = $contentHandle;
    }

    /**
     *
     */
    protected function detachContentResourceHandle()
    {
        $this->handle = null;
    }

    /**
     * Sets the content of the part to the passed string (effectively creates
     * a php://temp stream with the passed content and calls
     * attachContentResourceHandle with the opened stream).
     *
     * @param string $string
     */
    public function setContent($string)
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $string);
        rewind($handle);
        $this->attachContentResourceHandle($handle);
    }

    /**
     * Returns the resource stream handle for the part's content.
     *
     * The resource is automatically closed by MimePart's destructor and should
     * not be closed otherwise.
     *
     * @return resource
     */
    public function getContentResourceHandle()
    {
        return $this->handle;
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
            return stream_get_contents($this->handle);
        }
        return null;
    }

    /**
     * Adds a header with the given $name and $value.
     *
     * Creates a new \ZBateson\MailMimeParser\Header\AbstractHeader object and
     * registers it as a header.
     *
     * @param string $name
     * @param string $value
     */
    public function setRawHeader($name, $value)
    {
        $this->headers[strtolower($name)] = $this->headerFactory->newInstance($name, $value);
    }

    /**
     * Removes the header with the given name
     *
     * @param string $name
     */
    public function removeHeader($name)
    {
        unset($this->headers[strtolower($name)]);
    }

    /**
     * Returns the AbstractHeader object for the header with the given $name
     *
     * Note that mime headers aren't case sensitive.
     *
     * @param string $name
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader
     */
    public function getHeader($name)
    {
        if (isset($this->headers[strtolower($name)])) {
            return $this->headers[strtolower($name)];
        }
        return null;
    }

    /**
     * Returns the string value for the header with the given $name.
     *
     * Note that mime headers aren't case sensitive.
     *
     * @param string $name
     * @param string $defaultValue
     * @return string
     */
    public function getHeaderValue($name, $defaultValue = null)
    {
        $header = $this->getHeader($name);
        if (!empty($header)) {
            return $header->getValue();
        }
        return $defaultValue;
    }

    /**
     * Returns the full array of headers for this part.
     *
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Returns a parameter of the header $header, given the parameter named
     * $param.
     *
     * Only headers of type
     * \ZBateson\MailMimeParser\Header\ParameterHeader have parameters.
     * Content-Type and Content-Disposition are examples of headers with
     * parameters. "Charset" is a common parameter of Content-Type.
     *
     * @param string $header
     * @param string $param
     * @param string $defaultValue
     * @return string
     */
    public function getHeaderParameter($header, $param, $defaultValue = null)
    {
        $obj = $this->getHeader($header);
        if ($obj && $obj instanceof ParameterHeader) {
            return $obj->getValueFor($param, $defaultValue);
        }
        return $defaultValue;
    }

    /**
     * Sets the parent part.
     *
     * @param \ZBateson\MailMimeParser\MimePart $part
     */
    public function setParent(MimePart $part)
    {
        $this->parent = $part;
    }

    /**
     * Returns this part's parent.
     *
     * @return \ZBateson\MailMimeParser\MimePart
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets up a mailmimeparser-encode stream filter on the passed stream
     * resource handle if applicable and returns a reference to the filter.
     *
     * @param resource $handle
     * @return resource a reference to the appended stream filter or null
     */
    private function setCharsetStreamFilterOnStream($handle)
    {
        $contentType = strtolower($this->getHeaderValue('Content-Type', 'text/plain'));
        if (strpos($contentType, 'text/') === 0) {
            return stream_filter_append(
                $this->handle,
                'mailmimeparser-encode',
                STREAM_FILTER_READ,
                [
                    'charset' => 'UTF-8',
                    'to' => $this->getHeaderParameter('Content-Type', 'charset', 'ISO-8859-1')
                ]
            );
        }
        return null;
    }

    /**
     * Appends a stream filter the passed resource handle based on the type of
     * encoding for the current mime part.
     *
     * Unfortunately PHP seems to error out allocating memory for
     * stream_filter_make_writable in Base64EncodeStreamFilter using
     * STREAM_FILTER_WRITE, and HHVM doesn't seem to remove the filter properly
     * for STREAM_FILTER_READ, so the function appends a read filter on
     * $fromHandle if running through 'php', and a write filter on $toHandle if
     * using HHVM.
     *
     * @param resource $fromHandle
     * @param resource $toHandle
     * @param \ZBateson\MailMimeParser\Stream\StreamLeftover $leftovers
     * @return resource the stream filter
     */
    private function setTransferEncodingFilterOnStream($fromHandle, $toHandle, StreamLeftover $leftovers)
    {
        $encoding = strtolower($this->getHeaderValue('Content-Transfer-Encoding'));
        $params = [
            'line-length' => 76,
            'line-break-chars' => "\r\n",
            'leftovers' => $leftovers,
            'filename' => $this->getHeaderParameter(
                'Content-Type',
                'name',
                'null'
            )
        ];
        $typeToEncoding = [
            'quoted-printable' => 'convert.quoted-printable-encode',
            'base64' => 'convert.base64-encode',
            'x-uuencode' => 'mailmimeparser-uuencode',
        ];
        if (isset($typeToEncoding[$encoding])) {
            if (defined('HHVM_VERSION')) {
                return stream_filter_append(
                    $toHandle,
                    $typeToEncoding[$encoding],
                    STREAM_FILTER_WRITE,
                    $params
                );
            } else {
                return stream_filter_append(
                    $fromHandle,
                    $typeToEncoding[$encoding],
                    STREAM_FILTER_READ,
                    $params
                );
            }
        }
        return null;
    }

    /**
     * Returns true if the content-transfer-encoding header of the current part
     * is set to 'x-uuencode'.
     *
     * @return bool
     */
    private function isUUEncoded()
    {
        $encoding = strtolower($this->getHeaderValue('Content-Transfer-Encoding'));
        return ($encoding === 'x-uuencode');
    }

    /**
     * Filters out single line feed (CR or LF) characters from text input and
     * replaces them with CRLF, assigning the result to $read.  Also trims out
     * any starting and ending CRLF characters in the stream.
     *
     * @param string $read the read string, and where the result will be written
     *        to
     * @param bool $first set to true if this is the first set of read
     *        characters from the stream (ltrims CRLF)
     * @param string $lastChars contains any CRLF characters from the last $read
     *        line if it ended with a CRLF (because they're trimmed from the
     *        end, and get prepended to $read).
     */
    private function filterTextBeforeCopying(&$read, &$first, &$lastChars)
    {
        if ($first) {
            $first = false;
            $read = ltrim($read, "\r\n");
        }
        $read = $lastChars . $read;
        $read = preg_replace('/\r\n|\r|\n/', "\r\n", $read);
        $lastChars = '';
        $matches = null;
        if (preg_match('/[\r\n]+$/', $read, $matches)) {
            $lastChars = $matches[0];
            $read = rtrim($read, "\r\n");
        }
    }

    /**
     * Copies the content of the $fromHandle stream into the $toHandle stream,
     * maintaining the current read position in $fromHandle and writing
     * uuencode headers.
     *
     * @param resource $fromHandle
     * @param resource $toHandle
     */
    private function copyContentStream($fromHandle, $toHandle)
    {
        $pos = ftell($fromHandle);
        rewind($fromHandle);
        // changed from stream_copy_to_stream because hhvm seems to stop before
        // end of file for some reason
        $lastChars = '';
        $first = true;
        while (($read = fread($fromHandle, 1024)) != false) {
            if ($this->isTextPart()) {
                $this->filterTextBeforeCopying($read, $first, $lastChars);
            }
            fwrite($toHandle, $read);
        }
        fseek($fromHandle, $pos);
    }

    /**
     * Writes out headers and follows them with an empty line.
     *
     * @param resource $handle
     */
    protected function writeHeadersTo($handle)
    {
        foreach ($this->headers as $header) {
            fwrite($handle, "$header\r\n");
        }
        fwrite($handle, "\r\n");
    }

    /**
     * Writes out the content portion of the mime part based on the headers that
     * are set on the part, taking care of character/content-transfer encoding.
     *
     * @param resource $handle
     */
    protected function writeContentTo($handle)
    {
        if (!empty($this->handle)) {
            $filter = $this->setCharsetStreamFilterOnStream($handle);
            $leftovers = new StreamLeftover();
            $encodingFilter = $this->setTransferEncodingFilterOnStream($this->handle, $handle, $leftovers);
            $this->copyContentStream($this->handle, $handle);
            if ($encodingFilter !== null) {
                fflush($handle);
                stream_filter_remove($encodingFilter);
                fwrite($handle, $leftovers->encodedValue);
            }
            if ($filter !== null) {
                stream_filter_remove($filter);
            }
        }
    }

    /**
     * Writes out the MimePart to the passed resource.
     *
     * Takes care of character and content transfer encoding on the output based
     * on what headers are set.
     *
     * @param resource $handle
     */
    protected function writeTo($handle)
    {
        $this->writeHeadersTo($handle);
        $this->writeContentTo($handle);
    }
}
