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
        $this->handle = $contentHandle;
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
     * @return string
     */
    public function getHeaderValue($name)
    {
        $header = $this->getHeader($name);
        if (!empty($header)) {
            return $header->getValue();
        }
        return null;
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
}
