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
    /**
     * @var int The offset read start position for this part (beginning of
     * headers) in the message's stream.
     */
    private $streamPartStartPos = 0;
    
    /**
     * @var int The offset read end position for this part.  If the part is a
     * multipart mime part, the end position is after all of this parts
     * children.
     */
    private $streamPartEndPos = 0;
    
    /**
     * @var int The offset read start position in the message's stream for the
     * beginning of this part's content (body).
     */
    private $streamContentStartPos = 0;
    
    /**
     * @var int The offset read end position in the message's stream for the
     * end of this part's content (body).
     */
    private $streamContentEndPos = 0;

    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory
     */
    private $headerFactory;
    
    private $mimePartFactory;
    
    private $endBoundaryFound = false;
    private $mimeBoundary = false;
    private $headers = [];
    private $children = [];
    private $parent = null;
    private $properties = [];
    
    /**
     * @var ZBateson\MailMimeParser\Header\ParameterHeader
     */
    private $contentType = null;
    
    public function __construct(HeaderFactory $hf, MimePartFactory $mpf)
    {
        $this->headerFactory = $hf;
        $this->mimePartFactory = $mpf;
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
    public function addHeader($name, $value)
    {
        $this->headers[strtolower($name)] = [$name, $value];
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
                $this->headers['content-type'][1]
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
    
    public function getStreamPartStartPos()
    {
        return $this->streamPartStartPos;
    }

    public function getStreamPartEndPos()
    {
        return $this->streamPartEndPos;
    }

    public function getStreamContentStartPos()
    {
        return $this->streamContentStartPos;
    }

    public function getStreamContentEndPos()
    {
        return $this->streamContentEndPos;
    }

    public function setStreamPartStartPos($streamPartStartPos)
    {
        $this->streamPartStartPos = $streamPartStartPos;
    }

    public function setStreamPartEndPos($streamPartEndPos)
    {
        $this->streamPartEndPos = $streamPartEndPos;
    }

    public function setStreamContentStartPos($streamContentStartPos)
    {
        $this->streamContentStartPos = $streamContentStartPos;
    }

    public function setStreamContentEndPos($streamContentEndPos)
    {
        $this->streamContentEndPos = $streamContentEndPos;
    }
}
