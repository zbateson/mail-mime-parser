<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\ParameterHeader;
use ZBateson\MailMimeParser\Message\Writer\MimePartWriter;

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
     * @var \ZBateson\MailMimeParser\Message\MimePart parent part
     */
    protected $parent;

    /**
     * @var resource the content's resource handle
     */
    protected $handle;

    /**
     * @var \ZBateson\MailMimeParser\Message\MimePart[] array of parts in this
     *      message
     */
    protected $parts = [];

    /**
     * @var \ZBateson\MailMimeParser\Message\MimePart[] Maps mime types to parts
     * for looking up in getPartByMimeType
     */
    protected $mimeToPart = [];
    
    /**
     * @var \ZBateson\MailMimeParser\Message\Writer\MimePartWriter the part
     *      writer for this MimePart
     */
    protected $partWriter = null;

    /**
     * Sets up class dependencies.
     *
     * @param HeaderFactory $headerFactory
     * @param MimePartWriter $partWriter
     */
    public function __construct(HeaderFactory $headerFactory, MimePartWriter $partWriter)
    {
        $this->headerFactory = $headerFactory;
        $this->partWriter = $partWriter;
    }

    /**
     * Closes the attached resource handle.
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Adds the passed part to the parts array, and registers non-attachment/
     * non-multipart parts by their content type.
     *
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     */
    public function addPart(MimePart $part, $position = null)
    {
        if ($part->getParent() !== null && $this !== $part->getParent()) {
            $part->getParent()->addPart($part, $position);
        } elseif ($part !== $this) {
            array_splice($this->parts, ($position === null) ? count($this->parts) : $position, 0, [ $part ]);
        }
        $this->registerPart($part);
    }
    
    protected function registerPart(MimePart $part)
    {
        if ($part->getHeaderValue('Content-Disposition') === null && !$part->isMultiPart()) {
            $key = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
            $this->mimeToPart[$key] = $part;
        }
    }
    
    protected function unregisterPart(MimePart $part)
    {
        $key = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
        if (isset($this->mimeToPart[$key]) && $this->mimeToPart[$key] === $part) {
            unset($this->mimeToPart[$key]);
        }
    }
    
    /**
     * Unregisters the child part from this part.
     *
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     */
    public function removePart(MimePart $part)
    {
        $parent = $part->getParent();
        $this->unregisterPart($part);
        if ($this !== $parent && $parent !== null) {
            return $parent->removePart($part);
        } else {
            $position = array_search($part, $this->parts, true);
            if ($position !== false) {
                array_splice($this->parts, $position, 1);
                return $position;
            }
        }
        return null;
    }

    /**
     * Returns the non-text, non-HTML part at the given 0-based index, or null
     * if none is set.
     *
     * @param int $index
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function getPart($index)
    {
        if (!isset($this->parts[$index])) {
            return null;
        }
        return $this->parts[$index];
    }

    /**
     * Returns all child parts, and child parts of all children.
     * 
     * @return \ZBateson\MailMimeParser\Message\MimePart[]
     */
    public function getAllParts()
    {
        $aParts = [];
        foreach ($this->parts as $part) {
            $aParts = array_merge($aParts, [ $part ], $part->getAllParts());
        }
        return $aParts;
    }

    /**
     * Returns the total number of parts in this and all children.
     *
     * @return int
     */
    public function getPartCount()
    {
        return count($this->parts) + array_sum(
            array_map(function ($part) {
                return $part->getPartCount();
            },
            $this->parts)
        );
    }
    
    /**
     * Returns all direct child parts.
     * 
     * @return \ZBateson\MailMimeParser\Message\MimePart[]
     */
    public function getChildParts()
    {
        return $this->parts;
    }
    
    /**
     * Returns the number of direct children under this part.
     * 
     * @return \ZBateson\MailMimeParser\Message\MimePart[]
     */
    public function getChildCount()
    {
        return count($this->parts);
    }

    /**
     * Returns the part associated with the passed mime type if it exists.
     *
     * @param string $mimeType
     * @return \ZBateson\MailMimeParser\Message\MimePart or null
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
        if ($this->handle !== null) {
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
     * Returns true if this part's mime type is text/plain, text/html or has a
     * text/* and has a defined 'charset' attribute.
     * 
     * @return bool
     */
    public function isTextPart()
    {
        $type = $this->getHeaderValue('Content-Type', 'text/plain');
        if ($type === 'text/html' || $type === 'text/plain') {
            return true;
        }
        $charset = $this->getHeaderParameter('Content-Type', 'charset');
        return ($charset !== null && preg_match(
            '~text/\w+~i',
            $this->getHeaderValue('Content-Type', 'text/plain')
        ));
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
     * Returns the resource stream handle for the part's content or null if not
     * set.  rewind() is called on the stream before returning it.
     *
     * The resource is automatically closed by MimePart's destructor and should
     * not be closed otherwise.
     *
     * @return resource
     */
    public function getContentResourceHandle()
    {
        if (is_resource($this->handle)) {
            rewind($this->handle);
        }
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
        if ($header !== null) {
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
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     */
    public function setParent(MimePart $part)
    {
        $this->parent = $part;
    }

    /**
     * Returns this part's parent.
     *
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function getParent()
    {
        return $this->parent;
    }
}
