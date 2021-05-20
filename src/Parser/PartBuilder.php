<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Parser\Part\ParsedMessagePartFactory;
use ZBateson\MailMimeParser\Parser\Part\ParsedPartChildrenContainer;
use ZBateson\MailMimeParser\Parser\Part\ParsedPartStreamContainer;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use GuzzleHttp\Psr7\StreamWrapper;
use Psr\Http\Message\StreamInterface;

/**
 * Holds information about a part while it's being parsed, proxies calls between
 * parsed part containers (ParsedPartChildrenContainer,
 * ParsedPartStreamContainer) and the parser as more parts need to be parsed.
 *
 * The class holds:
 *  - a HeaderContainer to hold headers
 *  - stream positions (part start/end positions, content start/end)
 *  - parser markers, e.g. 'mimeBoundary, 'endBoundaryFound',
 *    'parentBoundaryFound', 'canHaveHeaders', 'isNonMimePart'
 *  - properties for UUEncoded parts (filename, mode)
 *  - the message's psr7 stream and a resource handle created from it (held
 *    only for a top-level PartBuilder representing the message, child
 *    PartBuilders do not duplicate/hold a separate stream).
 *  - ParsedPartChildrenContainer, ParsedPartStreamContainer to update children
 *    and streams dynamically as a part is parsed.
 * @author Zaahid Bateson
 */
class PartBuilder
{
    /**
     * @var MessagePartFactory the factory needed for creating the Message or
     *      MessagePart for the parsed part.
     */
    protected $messagePartFactory;

    /**
     * @var StreamFactory Used for creating streams for IMessageParts when
     *      creating them.
     */
    protected $streamFactory;

    /**
     * @var BaseParser Used for parsing parts as needed.
     */
    protected $baseParser;

    /**
     * @var int The offset read start position for this part (beginning of
     * headers) in the message's stream.
     */
    protected $streamPartStartPos = null;
    
    /**
     * @var int The offset read end position for this part.  If the part is a
     * multipart mime part, the end position is after all of this parts
     * children.
     */
    protected $streamPartEndPos = null;
    
    /**
     * @var int The offset read start position in the message's stream for the
     * beginning of this part's content (body).
     */
    protected $streamContentStartPos = null;
    
    /**
     * @var int The offset read end position in the message's stream for the
     * end of this part's content (body).
     */
    protected $streamContentEndPos = null;

    /**
     * @var boolean set to true once the end boundary of the currently-parsed
     *      part is found.
     */
    protected $endBoundaryFound = false;
    
    /**
     * @var boolean set to true once a boundary belonging to this parent's part
     *      is found.
     */
    protected $parentBoundaryFound = false;
    
    /**
     * @var boolean|null|string FALSE if not queried for in the content-type
     *      header of this part, NULL if the current part does not have a
     *      boundary, and otherwise contains the value of the boundary parameter
     *      of the content-type header if the part contains one.
     */
    protected $mimeBoundary = false;

    /**
     * @var bool true if the part can have headers (i.e. a top-level part, or a
     *      child part if the parent's end boundary hasn't been found and is not
     *      a discardable part).
     */
    protected $canHaveHeaders = true;

    /**
     * @var bool set to true when creating a PartBuilder for a non-mime message.
     */
    protected $isNonMimePart = false;

    /**
     * @var PartHeaderContainer a container for found and parsed headers.
     */
    protected $headerContainer;

    /**
     * @var PartBuilder the parent part.
     */
    protected $parent = null;
    
    /**
     * @var string[] key => value pairs of properties passed on to the 
     *      $messagePartFactory when constructing the Message and its children.
     */
    protected $properties = [];

    /**
     * @var StreamInterface the raw message input stream for a message, or null
     *      for a child part.
     */
    protected $messageStream;

    /**
     * @var resource the raw message input stream handle constructed from
     *      $messageStream or null for a child part
     */
    protected $messageHandle;

    /**
     * @var ParsedPartChildrenContainer
     */
    protected $partChildrenContainer;

    /**
     * @var ParsedPartStreamContainer
     */
    protected $partStreamContainer;

    /**
     * @var IMessagePart the last child that was added maintained to ensure all
     *      of the last added part was parsed before parsing the next part.
     */
    private $lastAddedChild;

    /**
     * @var IMessagePart the created part from this PartBuilder.
     */
    private $part;

    public function __construct(
        ParsedMessagePartFactory $mpf,
        StreamFactory $streamFactory,
        BaseParser $parser,
        PartHeaderContainer $headerContainer,
        StreamInterface $messageStream = null,
        PartBuilder $parent = null
    ) {
        $this->messagePartFactory = $mpf;
        $this->headerContainer = $headerContainer;
        $this->messageStream = $messageStream;
        $this->streamFactory = $streamFactory;
        $this->baseParser = $parser;
        if ($messageStream !== null) {
            $this->messageHandle = StreamWrapper::getResource($messageStream);
        }
        if ($parent !== null) {
            $this->parent = $parent;
            $this->canHaveHeaders = (!$parent->endBoundaryFound);
        }
        $this->setStreamPartStartPos($this->getMessageResourceHandlePos());
    }

    public function __destruct()
    {
        if ($this->messageHandle !== null) {
            fclose($this->messageHandle);
        }
    }

    public function setContainers(ParsedPartStreamContainer $streamContainer, ParsedPartChildrenContainer $childrenContainer = null)
    {
        $this->partStreamContainer = $streamContainer;
        $this->partChildrenContainer = $childrenContainer;
    }

    private function ensurePreviousSiblingRead()
    {
        if ($this->lastAddedChild !== null) {
            $this->lastAddedChild->hasContent();
            if ($this->lastAddedChild instanceof IMimePart) {
                $this->lastAddedChild->getAllParts();
            }
        }
    }

    public function parseContent()
    {
        if ($this->isContentParsed()) {
            return;
        }
        $this->baseParser->parseContent($this);
        $this->partStreamContainer->setContentStream(
            $this->streamFactory->getLimitedContentStream(
                $this->getStream(),
                $this
            )
        );
    }

    public function parseAll()
    {
        $part = $this->createMessagePart();
        $part->hasContent();
        if ($part instanceof IMimePart) {
            $part->getAllParts();
        }
    }

    public function parseNextChild()
    {
        $this->ensurePreviousSiblingRead();
        $this->parseContent();
        return $this->baseParser->parseNextChild($this);
    }

    public function addChildToContainer(IMessagePart $part)
    {
        $this->partChildrenContainer->add($part);
        $this->lastAddedChild = $part;
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
        $this->headerContainer->add($name, $value);
    }
    
    /**
     * Returns the HeaderContainer object containing parsed headers.
     * 
     * @return PartHeaderContainer
     */
    public function getHeaderContainer()
    {
        return $this->headerContainer;
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
     * Returns this PartBuilder's parent.
     * 
     * @return PartBuilder
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function getChild($offset)
    {
        return $this->partChildrenContainer[$offset];
    }

    public function setNonMimePart($bool)
    {
        $this->isNonMimePart = $bool;
    }

    public function isNonMimePart()
    {
        return $this->isNonMimePart;
    }

    /**
     * Returns true if either a Content-Type or Mime-Version header are defined
     * in this PartBuilder's headers.
     * 
     * @return boolean
     */
    public function isMimeMessagePart()
    {
        return ($this->headerContainer->exists('Content-Type') ||
            $this->headerContainer->exists('Mime-Version'));
    }
    
    /**
     * Returns a ParameterHeader representing the parsed Content-Type header for
     * this PartBuilder.
     * 
     * @return \ZBateson\MailMimeParser\Header\ParameterHeader
     */
    public function getContentType()
    {
        return $this->headerContainer->get('Content-Type');
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
                '~multipart/.*~i',
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

    public function isEndBoundaryFound()
    {
        return ($this->endBoundaryFound);
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
     * Returns true if the part's content contains headers.
     *
     * Top-level or non-discardable (i.e. parts before the end-boundary of the
     * parent) will return true;
     * 
     * @return boolean
     */
    public function canHaveHeaders()
    {
        return $this->canHaveHeaders;
    }

    public function getStream()
    {
        return ($this->messageStream !== null) ? $this->messageStream :
            $this->parent->getStream();
    }

    public function getMessageResourceHandle()
    {
        if ($this->messageStream === null) {
            return $this->parent->getMessageResourceHandle();
        }
        return $this->messageHandle;
    }

    public function getMessageResourceHandlePos()
    {
        return ftell($this->getMessageResourceHandle());
    }

    /**
     * Returns the offset for this part's stream within its parent stream.
     *
     * @return int
     */
    public function getStreamPartStartOffset()
    {
        return $this->streamPartStartPos;
    }

    /**
     * Returns the length of this part's stream.
     *
     * @return int
     */
    public function getStreamPartLength()
    {
        return $this->streamPartEndPos - $this->streamPartStartPos;
    }

    /**
     * Returns the offset for this part's content within its part stream.
     *
     * @return int
     */
    public function getStreamContentStartOffset()
    {
        return $this->streamContentStartPos;
    }

    /**
     * Returns the length of this part's content stream.
     *
     * @return int
     */
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

    public function isContentParsed()
    {
        return ($this->streamContentEndPos !== null);
    }

    /**
     * Creates a MessagePart and returns it using the PartBuilder's
     * MessagePartFactory passed in during construction.
     *
     * @return IMessagePart
     */
    public function createMessagePart()
    {
        if (!$this->part) {
            $this->part = $this->messagePartFactory->newInstance(
                $this,
                ($this->parent !== null) ? $this->parent->createMessagePart() : null
            );
            if ($this->parent !== null && !$this->parent->endBoundaryFound) {
                // endBoundaryFound would indicate this is a discardable part
                // after the end boundary (some mailers seem to add identifiers)
                $this->parent->addChildToContainer($this->part);
            }
        }
        return $this->part;
    }
}
