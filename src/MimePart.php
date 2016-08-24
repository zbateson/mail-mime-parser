<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\ParameterHeader;

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
     * Either adds the passed part to $this->textPart if its content type is
     * text/plain, to $this->htmlPart if it's text/html, or adds the part to the
     * parts array otherwise.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     */
    public function addPart(MimePart $part)
    {
        $this->parts[] = $part;
        $key = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
        $this->mimeToPart[$key] = $part;
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
     * Returns the AbstractHeader object for the header with the given $name
     * 
     * Note that mime headers aren't case sensitive.
     * 
     * @param string $name
     * @return \ZBateson\MailMimeParser\Header\Header
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
     * @param resource $handle
     * @return resource the stream filter
     */
    private function setTransferEncodingFilterOnStream($handle)
    {
        $encoding = strtolower($this->getHeaderValue('Content-Transfer-Encoding'));
        $params = [
            'line-length' => 76,
            'line-break-chars' => "\r\n",
        ];
        $typeToEncoding = [
            'quoted-printable' => 'convert.quoted-printable-encode',
            'base64' => 'convert.base64-encode',
            'x-uuencode' => 'mailmimeparser-uuencode',
        ];
        if (isset($typeToEncoding[$encoding])) {
            return $encodingFilter = stream_filter_append(
                $handle,
                $typeToEncoding[$encoding],
                STREAM_FILTER_READ,
                $params
            );
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
     * Writes out the header for a uuencoded part to the passed stream resource
     * handle.
     * 
     * @param resource $handle
     */
    private function writeUUEncodingHeader($handle)
    {
        fwrite($handle, 'begin 666 ' . $this->getHeaderParameter(
            'Content-Disposition',
            'filename',
            $this->getHeaderParameter(
                'Content-Type',
                'name',
                'null'
            )
        ));
    }
    
    /**
     * Writes out the footer for a uuencoded part to the passed stream resource
     * handle.
     * 
     * @param resource $handle
     */
    private function writeUUEncodingFooter($handle)
    {
        fwrite($handle, "\r\n`\r\nend\r\n\r\n");
    }
    
    /**
     * Copies the content of the $fromHandle stream into the $toHandle stream,
     * maintaining the current read position in $fromHandle and writing
     * uuencode headers.
     * 
     * @param resource $fromHandle
     * @param resource $toHandle
     * @param bool $isUUEncoded
     */
    private function copyContentStream($fromHandle, $toHandle, $isUUEncoded)
    {
        $pos = ftell($fromHandle);
        rewind($fromHandle);

        if ($isUUEncoded) {
            $this->writeUUEncodingHeader($toHandle);
            stream_copy_to_stream($fromHandle, $toHandle);
            $this->writeUUEncodingFooter($toHandle);
        } else {
            stream_copy_to_stream($fromHandle, $toHandle);
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
            $encodingFilter = $this->setTransferEncodingFilterOnStream($this->handle);
            $this->copyContentStream($this->handle, $handle, $this->isUUEncoded());
            if ($encodingFilter !== null) {
                stream_filter_remove($encodingFilter);
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
