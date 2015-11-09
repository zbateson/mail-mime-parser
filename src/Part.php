<?php
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;

/**
 * Represents a single part of a multi-part mime message.
 * 
 * A Part object may have any number of child parts, or may be a child itself
 * with its own parent or parents.
 * 
 * The content of the part can be read from its PartStream resource handle,
 * accessible via Part::getContentResourceHanlde.
 *
 * @author Zaahid Bateson
 */
class Part
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      object used for created headers
     */
    protected $headerFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Header\Header[] array of header objects
     */
    protected $headers;
    
    /**
     * @var \ZBateson\MailMimeParser\Part parent part
     */
    protected $parent;
    
    /**
     * @var resource the content's resource handle 
     */
    protected $handle;
    
    /**
     * Constructs a Part instance.
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
     * is closed when the Part object is destroyed.
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
     * The resource is automatically closed by Part's destructor and should not
     * be closed otherwise.
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
     * Creates a new \ZBateson\MailMimeParser\Header\Header object and adds it
     * to Part::headers.
     * 
     * @param string $name
     * @param string $value
     */
    public function setRawHeader($name, $value)
    {
        $this->headers[strtolower($name)] = $this->headerFactory->newInstance($name, $value);
    }
    
    /**
     * Returns the Header object for the header with the given $name
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
     * Returns the string value of the header with the given $name.
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
            return $header->value;
        }
        return null;
    }
    
    /**
     * Returns the full array of headers for this part.
     * 
     * @return \ZBateson\MailMimeParser\Header\Header[]
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
     * \ZBateson\MailMimeParser\Header\ValueParametersHeader have parameters.
     * Content-Type and Content-Disposition are headers with parameters.
     * 
     * @param string $header
     * @param string $param
     * @return string
     */
    public function getHeaderParameter($header, $param)
    {
        $obj = $this->getHeader($header);
        if (isset($obj->params[$param])) {
            return $obj->params[$param];
        }
        return null;
    }
    
    /**
     * Sets the parent part.
     * 
     * @param \ZBateson\MailMimeParser\Part $part
     */
    public function setParent(Part $part)
    {
        $this->parent = $part;
    }
    
    /**
     * Returns this part's parent.
     * 
     * @return \ZBateson\MailMimeParser\Part
     */
    public function getParent()
    {
        return $this->parent;
    }
}
