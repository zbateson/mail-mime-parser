<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\ParameterHeader;
use ZBateson\MailMimeParser\Message\PartFilterFactory;
use ZBateson\MailMimeParser\Message\PartFilter;

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
class MimePart extends MessagePart
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      object used for created headers
     */
    protected $headerFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\PartFilterFactory factory object
     *      responsible for create PartFilters
     */
    protected $partFilterFactory;

    /**
     * @var \ZBateson\MailMimeParser\Message\Part\MessagePart[] array of child
     *      parts
     */
    protected $children = [];
    
    /**
     * @var string[][] array of headers, with keys set to lower-cased,
     *      alphanumeric characters of the header's name, and values set to an
     *      array of 2 elements, the first being the header's original name with
     *      non-alphanumeric characters and original case, and the second set to
     *      the header's value.
     */
    protected $rawHeaders;
    
    /**
     * @var \ZBateson\MailMimeParser\Header\AbstractHeader[] array of parsed
     * header objects populated on-demand, the key is set to the header's name
     * lower-cased, and with non-alphanumeric characters removed.
     */
    protected $headers;

    /**
     * Sets up class dependencies.
     *
     * @param HeaderFactory $headerFactory 
     * @param PartFilterFactory $partFilterFactory
     * @param string $messageObjectId
     * @param PartBuilder $partBuilder
     * @param PartStreamFilterManager $partStreamFilterManager
     */
    public function __construct(
        HeaderFactory $headerFactory,
        PartFilterFactory $partFilterFactory,
        $messageObjectId,
        PartBuilder $partBuilder,
        PartStreamFilterManager $partStreamFilterManager
    ) {
        parent::__construct($messageObjectId, $partBuilder, $partStreamFilterManager);
        $this->headerFactory = $headerFactory;
        $this->partFilterFactory = $partFilterFactory;

        $pbChildren = $partBuilder->getChildren();
        if (!empty($pbChildren)) {
            $this->children = array_map(function ($child) use ($messageObjectId) {
                $childPart = $child->createMessagePart($messageObjectId);
                $childPart->parent = $this;
                return $childPart;
            }, $pbChildren);
        }
        $this->headers['contenttype'] = $partBuilder->getContentType();
        $this->rawHeaders = $partBuilder->getRawHeaders();
    }
    
    /**
     * Returns all parts, including the current object, and all children below
     * it (including children of children, etc...)
     * 
     * @return MessagePart[]
     */
    protected function getAllNonFilteredParts()
    {
        $parts = [ $this ];
        foreach ($this->children as $part) {
            if ($part instanceof MimePart) {
                $parts = array_merge(
                    $parts,
                    $part->getAllNonFilteredParts()
                );
            } else {
                array_push($parts, $part);
            }
        }
        return $parts;
    }

    /**
     * Returns the part at the given 0-based index, or null if none is set.
     * 
     * Note that the first part returned is the current part itself.  This is
     * often desirable for queries with a PartFilter, e.g. looking for a
     * MessagePart with a specific Content-Type that may be satisfied by the
     * current part.
     *
     * @param int $index
     * @param PartFilter $filter
     * @return MessagePart
     */
    public function getPart($index, PartFilter $filter = null)
    {
        $parts = $this->getAllParts($filter);
        if (!isset($parts[$index])) {
            return null;
        }
        return $parts[$index];
    }

    /**
     * Returns the current part, all child parts, and child parts of all
     * children optionally filtering them with the provided PartFilter.
     * 
     * The first part returned is always the current MimePart.  This is often
     * desirable as it may be a valid MimePart for the provided PartFilter.
     * 
     * @param PartFilter $filter an optional filter
     * @return MessagePart[]
     */
    public function getAllParts(PartFilter $filter = null)
    {
        $parts = $this->getAllNonFilteredParts();
        if (!empty($filter)) {
            return array_values(array_filter(
                $parts,
                [ $filter, 'filter' ]
            ));
        }
        return $parts;
    }

    /**
     * Returns the total number of parts in this and all children.
     * 
     * Note that the current part is considered, so the minimum getPartCount is
     * 1 without a filter.
     *
     * @param PartFilter $filter
     * @return int
     */
    public function getPartCount(PartFilter $filter = null)
    {
        return count($this->getAllParts($filter));
    }
    
    /**
     * Returns the direct child at the given 0-based index, or null if none is
     * set.
     *
     * @param int $index
     * @param PartFilter $filter
     * @return MessagePart
     */
    public function getChild($index, PartFilter $filter = null)
    {
        $parts = $this->getChildParts($filter);
        if (!isset($parts[$index])) {
            return null;
        }
        return $parts[$index];
    }
    
    /**
     * Returns all direct child parts.
     * 
     * If a PartFilter is provided, the PartFilter is applied before returning.
     * 
     * @param PartFilter $filter
     * @return MessagePart[]
     */
    public function getChildParts(PartFilter $filter = null)
    {
        if ($filter !== null) {
            return array_values(array_filter($this->children, [ $filter, 'filter' ]));
        }
        return $this->children;
    }
    
    /**
     * Returns the number of direct children under this part.
     * 
     * @param PartFilter $filter
     * @return int
     */
    public function getChildCount(PartFilter $filter = null)
    {
        return count($this->getChildParts($filter));
    }

    /**
     * Returns the part associated with the passed mime type if it exists.
     *
     * @param string $mimeType
     * @return MessagePart or null
     */
    public function getPartByMimeType($mimeType, $index = 0)
    {
        $partFilter = $this->partFilterFactory->newFilterFromContentType($mimeType);
        return $this->getPart($index, $partFilter);
    }
    
    /**
     * Returns an array of all parts associated with the passed mime type if any
     * exist or null otherwise.
     *
     * @param string $mimeType
     * @return MessagePart[] or null
     */
    public function getAllPartsByMimeType($mimeType)
    {
        $partFilter = $this->partFilterFactory->newFilterFromContentType($mimeType);
        return $this->getAllParts($partFilter);
    }
    
    /**
     * Returns the number of parts matching the passed $mimeType
     * 
     * @param string $mimeType
     * @return int
     */
    public function getCountOfPartsByMimeType($mimeType)
    {
        $partFilter = $this->partFilterFactory->newFilterFromContentType($mimeType);
        return $this->getPartCount($partFilter);
    }

    /**
     * Returns true if this part's mime type is multipart/*
     *
     * @return bool
     */
    public function isMultiPart()
    {
        // casting to bool, preg_match returns 1 for true
        return (bool) (preg_match(
            '~multipart/\w+~i',
            $this->getContentType()
        ));
    }
    
    /**
     * Returns a filename for the part if one is defined, or null otherwise.
     * 
     * @return string
     */
    public function getFilename()
    {
        return $this->getHeaderParameter(
            'Content-Disposition',
            'filename',
            $this->getHeaderParameter(
                'Content-Type',
                'name'
            )
        );
    }
    
    /**
     * Returns true.
     * 
     * @return bool
     */
    public function isMime()
    {
        return true;
    }
    
    /**
     * Returns true if this part's mime type is text/plain, text/html or if the
     * Content-Type header defines a charset.
     * 
     * @return bool
     */
    public function isTextPart()
    {
        return ($this->getCharset() !== null);
    }
    
    /**
     * Returns the lower-cased, trimmed value of the Content-Type header.
     * 
     * Parses the Content-Type header, defaults to returning text/plain if not
     * defined.
     * 
     * @return string
     */
    public function getContentType($default = 'text/plain')
    {
        return trim(strtolower($this->getHeaderValue('Content-Type', $default)));
    }
    
    /**
     * Returns the upper-cased charset of the Content-Type header's charset
     * parameter if set, US-ASCII if the Content-Type is text/plain or text/html
     * and the charset parameter isn't set, or null otherwise.
     * 
     * @return string
     */
    public function getCharset()
    {
        $charset = $this->getHeaderParameter('Content-Type', 'charset');
        if ($charset === null) {
            $contentType = $this->getContentType();
            if ($contentType === 'text/plain' || $contentType === 'text/html') {
                return 'US-ASCII';
            }
            return null;
        }
        return trim(strtoupper($charset));
    }
    
    /**
     * Returns the content's disposition, defaulting to 'inline' if not set.
     * 
     * @return string
     */
    public function getContentDisposition($default = 'inline')
    {
        return strtolower($this->getHeaderValue('Content-Disposition', $default));
    }
    
    /**
     * Returns the content-transfer-encoding used for this part, defaulting to
     * '7bit' if not set.
     * 
     * @return string
     */
    public function getContentTransferEncoding($default = '7bit')
    {
        static $translated = [
            'x-uue' => 'x-uuencode',
            'uue' => 'x-uuencode',
            'uuencode' => 'x-uuencode'
        ];
        $type = strtolower($this->getHeaderValue('Content-Transfer-Encoding', $default));
        if (isset($translated[$type])) {
            return $translated[$type];
        }
        return $type;
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
        $nameKey = preg_replace('/[^a-z0-9]/', '', strtolower($name));
        if (isset($this->rawHeaders[$nameKey])) {
            if (!isset($this->headers[$nameKey])) {
                $this->headers[$nameKey] = $this->headerFactory->newInstance(
                    $this->rawHeaders[$nameKey][0],
                    $this->rawHeaders[$nameKey][1]
                );
            }
            return $this->headers[$nameKey];
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
}
