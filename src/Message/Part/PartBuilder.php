<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Header\HeaderFactory;

/**
 * Used by MessageParser to keep information about a parsed message as an
 * intermediary before creating a Message object and its MessagePart children.
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
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory used to parse a
     *      Content-Type header when needed.
     */
    private $headerFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\Part\MessagePartFactory the factory
     *      needed for creating the Message or MessagePart for the parsed part.
     */
    private $messagePartFactory;
    
    /**
     * @var boolean set to true once the end boundary of the currently-parsed
     *      part is found.
     */
    private $endBoundaryFound = false;
    
    /**
     * @var boolean set to true once a boundary belonging to this parent's part
     *      is found.
     */
    private $parentBoundaryFound = false;
    
    /**
     * @var boolean|null|string false if not queried for in the content-type
     *      header of this part, null if the current part does not have a
     *      boundary, or the value of the boundary parameter of the content-type
     *      header if the part contains one.
     */
    private $mimeBoundary = false;
    
    /**
     * @var string[][] an array of headers on the current part.  The key index
     *      is set to the lower-cased, alphanumeric-only, name of the header
     *      (after stripping out non-alphanumeric characters, e.g. contenttype)
     *      and each element containing an array of 2 strings, the first being
     *      the original name of the header, and the second being the value.
     */
    private $headers = [];
    
    /**
     * @var PartBuilder[] an array of children found below this part for a mime
     *      email
     */
    private $children = [];
    
    /**
     * @var PartBuilder the parent part.
     */
    private $parent = null;
    
    /**
     * @var string[] key => value pairs of properties passed on to the 
     *      $messagePartFactory when constructing the Message and its children.
     */
    private $properties = [];
    
    /**
     * @var ZBateson\MailMimeParser\Header\ParameterHeader parsed content-type
     *      header.
     */
    private $contentType = null;
    
    /**
     * Sets up class dependencies.
     * 
     * @param HeaderFactory $hf
     * @param \ZBateson\MailMimeParser\Message\Part\MessagePartFactory $mpf
     */
    public function __construct(
        HeaderFactory $hf,
        MessagePartFactory $mpf
    ) {
        $this->headerFactory = $hf;
        $this->messagePartFactory = $mpf;
    }
    
    /**
     * Adds a header with the given $name and $value to the headers array.
     *
     * Removes non-alphanumeric characters from $name, and sets it to lower-case
     * to use as a key in the private headers array.  Sets the original $name
     * and $value as elements in the headers' array value for the calculated
     * key.
     *
     * @param string $name
     * @param string $value
     */
    public function addHeader($name, $value)
    {
        $nameKey = preg_replace('/[^a-z0-9]/', '', strtolower($name));
        $this->headers[$nameKey] = [$name, $value];
    }
    
    /**
     * Returns the raw headers added to this PartBuilder as an array consisting
     * of:
     * 
     * Keys set to the name of the header, in all lowercase, and with non-
     * alphanumeric characters removed (e.g. Content-Type becomes contenttype).
     * 
     * The value is an array of two elements.  The first is the original header
     * name (e.g. Content-Type) and the second is the raw string value of the
     * header, e.g. 'text/html; charset=utf8'.
     * 
     * @return array
     */
    public function getRawHeaders()
    {
        return $this->headers;
    }
    
    /**
     * Sets the specified property denoted by $name to $value.
     * 
     * @param string $name
     * @param mixed $value
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }
    
    /**
     * Returns the value of the property with the given $name.
     * 
     * @param string $name
     * @return mixed
     */
    public function getProperty($name)
    {
        if (!isset($this->properties[$name])) {
            return null;
        }
        return $this->properties[$name];
    }
    
    /**
     * Registers the passed PartBuilder as a child of the current PartBuilder.
     * 
     * @param \ZBateson\MailMimeParser\Message\PartBuilder $partBuilder
     */
    public function addChild(PartBuilder $partBuilder)
    {
        $partBuilder->parent = $this;
        // discard parts added after the end boundary
        if (!$this->endBoundaryFound) {
            $this->children[] = $partBuilder;
        }
    }
    
    /**
     * Returns all children PartBuilder objects.
     * 
     * @return \ZBateson\MailMimeParser\Message\PartBuilder[]
     */
    public function getChildren()
    {
        return $this->children;
    }
    
    /**
     * Returns this PartBuilder's parent.
     * 
     * @return PartBuilder
     */
    public function getParent()
    {
        return $this->parent;
    }
    
    /**
     * Returns true if either a Content-Type or Mime-Version header are defined
     * in this PartBuilder's headers.
     * 
     * @return boolean
     */
    public function isMime()
    {
        return (isset($this->headers['contenttype'])
            || isset($this->headers['mimeversion']));
    }
    
    /**
     * Returns a ParameterHeader representing the parsed Content-Type header for
     * this PartBuilder.
     * 
     * @return \ZBateson\MailMimeParser\Header\ParameterHeader
     */
    public function getContentType()
    {
        if ($this->contentType === null && isset($this->headers['contenttype'])) {
            $this->contentType = $this->headerFactory->newInstance(
                'Content-Type',
                $this->headers['contenttype'][1]
            );
        }
        return $this->contentType;
    }
    
    /**
     * Returns the parsed boundary parameter of the Content-Type header if set
     * for a multipart message part.
     * 
     * @return string
     */
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
     * @return boolean
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
    
    /**
     * Returns true if the passed $line of read input matches this PartBuilder's
     * mime boundary, or any of its parent's mime boundaries for a multipart
     * message.
     * 
     * If the passed $line is the ending boundary for the current PartBuilder,
     * $this->isEndBoundaryFound will return true after.
     * 
     * @param string $line
     * @return boolean
     */
    public function setEndBoundaryFound($line)
    {
        $boundary = $this->getMimeBoundary();
        if ($this->parent !== null && $this->parent->setEndBoundaryFound($line)) {
            $this->parentBoundaryFound = true;
            return true;
        } elseif ($boundary !== null) {
            if ($line === "--$boundary--") {
                $this->endBoundaryFound = true;
                return true;
            } elseif ($line === "--$boundary") {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns true if MessageParser passed an input line to setEndBoundary that
     * matches a parent's mime boundary, and the following input belongs to a
     * new part under its parent.
     * 
     * @return boolean
     */
    public function isParentBoundaryFound()
    {
        return ($this->parentBoundaryFound);
    }
    
    /**
     * Called once EOF is reached while reading content.  The method sets the
     * flag used by PartBuilder::isParentBoundaryFound to true on this part and
     * all parent PartBuilders.
     */
    public function setEof()
    {
        $this->parentBoundaryFound = true;
        if ($this->parent !== null) {
            $this->parent->parentBoundaryFound = true;
        }
    }
    
    /**
     * Returns false if this part has a parent part in which endBoundaryFound is
     * set to true (i.e. this isn't a discardable part following the parent's
     * end boundary line).
     * 
     * @return booelan
     */
    public function canHaveHeaders()
    {
        return ($this->parent === null || !$this->parent->endBoundaryFound);
    }

    public function getStreamPartStartOffset()
    {
        if ($this->parent) {
            return $this->streamPartStartPos - $this->parent->streamPartStartPos;
        }
        return $this->streamPartStartPos;
    }
    
    public function getStreamPartLength()
    {
        return $this->streamPartEndPos - $this->streamPartStartPos;
    }

    public function getStreamContentStartOffset()
    {
        if ($this->parent) {
            return $this->streamContentStartPos - $this->parent->streamPartStartPos;
        }
        return $this->streamContentStartPos;
    }

    public function getStreamContentLength()
    {
        return $this->streamContentEndPos - $this->streamContentStartPos;
    }

    /**
     * Sets the start position of the part in the input stream.
     * 
     * @param int $streamPartStartPos
     */
    public function setStreamPartStartPos($streamPartStartPos)
    {
        $this->streamPartStartPos = $streamPartStartPos;
    }

    /**
     * Sets the end position of the part in the input stream, and also calls
     * parent->setParentStreamPartEndPos to expand to parent parts.
     * 
     * @param int $streamPartEndPos
     */
    public function setStreamPartEndPos($streamPartEndPos)
    {
        $this->streamPartEndPos = $streamPartEndPos;
        if ($this->parent !== null) {
            $this->parent->setStreamPartEndPos($streamPartEndPos);
        }
    }

    /**
     * Sets the start position of the content in the input stream.
     * 
     * @param int $streamContentStartPos
     */
    public function setStreamContentStartPos($streamContentStartPos)
    {
        $this->streamContentStartPos = $streamContentStartPos;
    }

    /**
     * Sets the end position of the content and part in the input stream.
     * 
     * @param int $streamContentEndPos
     */
    public function setStreamPartAndContentEndPos($streamContentEndPos)
    {
        $this->streamContentEndPos = $streamContentEndPos;
        $this->setStreamPartEndPos($streamContentEndPos);
    }

    /**
     * Creates a MessagePart and returns it using the PartBuilder's
     * MessagePartFactory passed in during construction.
     * 
     * @param StreamInterface $stream
     * @return MessagePart
     */
    public function createMessagePart(StreamInterface $stream)
    {
        return $this->messagePartFactory->newInstance(
            $stream,
            $this
        );
    }
}
