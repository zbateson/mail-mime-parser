<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

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
     * @var string the PartStream protocol used to create part and content
     *      filenames for fopen
     */
    private $streamWrapperProtocol = null;
    
    /**
     * Sets up class dependencies.
     * 
     * @param HeaderFactory $hf
     * @param \ZBateson\MailMimeParser\Message\Part\MessagePartFactory $mpf
     * @param string $streamWrapperProtocol
     */
    public function __construct(
        HeaderFactory $hf,
        MessagePartFactory $mpf,
        $streamWrapperProtocol
    ) {
        $this->headerFactory = $hf;
        $this->messagePartFactory = $mpf;
        $this->streamWrapperProtocol = $streamWrapperProtocol;
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
        $partBuilder->setParent($this);
        $this->children[] = $partBuilder;
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
     * Registers the passed PartBuilder as the parent of the current
     * PartBuilder.
     * 
     * @param \ZBateson\MailMimeParser\Message\Part\PartBuilder $partBuilder
     */
    public function setParent(PartBuilder $partBuilder)
    {
        $this->parent = $partBuilder;
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
    
    /**
     * Returns true if MessageParser passed an input line to setEndBoundary that
     * indicates the end of the part.
     * 
     * @return boolean
     */
    public function isEndBoundaryFound()
    {
        return $this->endBoundaryFound;
    }
    
    /**
     * Constructs and returns a filename where the part can be read from the
     * passed $messageObjectId.
     * 
     * @param string $messageObjectId the message object id
     * @return string
     */
    public function getStreamPartFilename($messageObjectId)
    {
        if ($this->streamPartEndPos === 0) {
            return null;
        }
        return $this->streamWrapperProtocol . '://' . $messageObjectId
            . '?start=' . $this->streamPartStartPos . '&end='
            . $this->streamPartEndPos;
    }
    
    /**
     * Constructs and returns a filename where the part's content can be read
     * from the passed $messageObjectId.
     * 
     * @param string $messageObjectId the message object id
     * @return string
     */
    public function getStreamContentFilename($messageObjectId)
    {
        if ($this->streamContentEndPos === 0) {
            return null;
        }
        return $this->streamWrapperProtocol . '://' . $messageObjectId
            . '?start=' . $this->streamContentStartPos . '&end='
            . $this->streamContentEndPos;
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
     * Sets the end position of the part in the input stream.
     * 
     * @param int $streamPartEndPos
     */
    public function setStreamPartEndPos($streamPartEndPos)
    {
        $this->streamPartEndPos = $streamPartEndPos;
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
     * Sets the end position of the content in the input stream.
     * 
     * @param int $streamContentEndPos
     */
    public function setStreamContentEndPos($streamContentEndPos)
    {
        $this->streamContentEndPos = $streamContentEndPos;
    }
    
    /**
     * Creates a MessagePart and returns it using the PartBuilder's
     * MessagePartFactory passed in during construction.
     * 
     * @param string $messageObjectId
     * @return MessagePart
     */
    public function createMessagePart($messageObjectId)
    {
        return $this->messagePartFactory->newInstance(
            $messageObjectId,
            $this
        );
    }
}
