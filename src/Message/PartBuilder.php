<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Header\HeaderFactory;

/**
 * Description of MimePartBuilder
 *
 * @author Zaahid Bateson
 */
class PartBuilder
{
    public $streamPartReadStartPos = 0;
    public $streamContentReadStartPos = 0;
    public $streamContentReadEndPos = 0;
    public $streamPartReadEndPos = 0;
    
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory
     */
    private $headerFactory;
    
    private $endBoundaryFound = false;
    private $mimeBoundary = false;
    private $headers = [];
    private $children = [];
    private $parent = null;
    
    /**
     * @var ZBateson\MailMimeParser\Header\ParameterHeader
     */
    private $contentType = null;
    
    public function __construct(HeaderFactory $hf)
    {
        $this->headerFactory = $hf;
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
        $this->headers[strtolower($name)] = $value;
    }
    
    /**
     * Registers the passed part as a child of the current part.
     * 
     * @param \ZBateson\MailMimeParser\Message\PartBuilder $partBuilder
     */
    public function addChild(PartBuilder $partBuilder)
    {
        $partBuilder->setParent($this);
        $this->children[] = $partBuilder;
    }
    
    public function setParent(PartBuilder $partBuilder)
    {
        $this->parent = $partBuilder;
    }
    
    public function getParent()
    {
        return $this->parent;
    }
    
    /**
     * Returns true if either a Content-Type or Mime-Version header are defined
     * in this part.
     * 
     * @return bool
     */
    public function isMime()
    {
        return (isset($this->headers['content-type'])
            || isset($this->headers['mime-version']));
    }
    
    /**
     * 
     * @return \ZBateson\MailMimeParser\Header\ParameterHeader
     */
    public function getContentType()
    {
        if ($this->contentType === null && $this->isset($this->headers['content-type'])) {
            $this->contentType = $this->headerFactory->newInstance(
                'content-type',
                $this->headers['content-type']
            );
        }
        return $this->contentType;
    }
    
    public function getMimeBoundary()
    {
        if ($this->mimeBoundary === false) {
            $this->mimeBoundary = null;
            $contentType = $this->getContentType();
            if ($contentType !== null) {
                $this->mimeBoundary = $contentType->getValueFor('boundary');
            }
        }
        return $this->mimeBoundary;
    }
    
    /**
     * Returns true if this part's content-type is multipart/*
     *
     * @return bool
     */
    public function isMultiPart()
    {
        $contentType = $this->getContentType();
        if ($contentType !== null) {
            // casting to bool, preg_match returns 1 for true
            return (bool) (preg_match(
                '~multipart/\w+~i',
                $contentType->getValue()
            ));
        }
        return false;
    }
    
    public function setEndBoundary($line)
    {
        $boundary = $this->getMimeBoundary();
        if ($boundary !== null) {
            if ($line === "--$boundary--") {
                $this->endBoundaryFound = true;
                return true;
            } elseif ($line === "--$boundary") {
                return true;
            }
        } elseif ($this->getParent() !== null && $this->getParent()->setEndBoundary($line)) {
            return true;
        }
        return false;
    }
    
    public function isEndBoundaryFound()
    {
        return $this->endBoundaryFound;
    }
}
