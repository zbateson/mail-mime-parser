<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7\StreamWrapper;
use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;
use ZBateson\MailMimeParser\Header\HeaderConsts;

/**
 * Holds information about a part while it's being parsed, proxies calls between
 * parsed part containers (ParserPartChildrenContainer,
 * ParserPartStreamContainer) and the parser as more parts need to be parsed.
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
 *  - ParserPartChildrenContainer, ParserPartStreamContainer to update children
 *    and streams dynamically as a part is parsed.
 * @author Zaahid Bateson
 */
class PartBuilder
{
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
     * @var PartHeaderContainer The parsed part's headers.
     */
    protected $headerContainer;

    /**
     * @var StreamInterface the raw message input stream for a message, or null
     *      for a child part.
     */
    protected $messageStream = null;

    /**
     * @var resource the raw message input stream handle constructed from
     *      $messageStream or null for a child part
     */
    protected $messageHandle = null;

    /**
     * @var ParserPartProxy
     */
    private $parent = null;

    public function __construct(PartHeaderContainer $headerContainer, StreamInterface $messageStream = null, ParserPartProxy $parent = null)
    {
        $this->headerContainer = $headerContainer;
        $this->messageStream = $messageStream;
        $this->parent = $parent;
        if ($messageStream !== null) {
            $this->messageHandle = StreamWrapper::getResource($messageStream);
        }
        $this->setStreamPartStartPos($this->getMessageResourceHandlePos());
    }

    public function __destruct()
    {
        if ($this->messageHandle) {
            fclose($this->messageHandle);
        }
    }

    /**
     *
     * @return ParserPartProxy
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns this part's PartHeaderContainer.
     *
     * @return PartHeaderContainer the container
     */
    public function getHeaderContainer()
    {
        return $this->headerContainer;
    }

    public function getStream()
    {
        return ($this->parent !== null) ?
            $this->parent->getStream() :
            $this->messageStream;
    }

    public function getMessageResourceHandle()
    {
        return ($this->parent !== null) ?
            $this->parent->getMessageResourceHandle() :
            $this->messageHandle;
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
    public function getStreamPartStartPos()
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
    public function getStreamContentStartPos()
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

    public function isMime()
    {
        if ($this->getParent() !== null) {
            return $this->getParent()->isMime();
        }
        return ($this->headerContainer->exists(HeaderConsts::CONTENT_TYPE) ||
            $this->headerContainer->exists(HeaderConsts::MIME_VERSION));
    }
}
