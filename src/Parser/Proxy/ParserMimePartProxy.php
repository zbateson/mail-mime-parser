<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Header\HeaderConsts;

/**
 * A bi-directional parser-to-part proxy for IMimeParts.
 *
 * @author Zaahid Bateson
 */
class ParserMimePartProxy extends ParserPartProxy
{
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
     * @var bool|null|string FALSE if not queried for in the content-type
     *      header of this part, NULL if the current part does not have a
     *      boundary, and otherwise contains the value of the boundary parameter
     *      of the content-type header if the part contains one.
     */
    protected $mimeBoundary = false;

    /**
     * @var bool true once all children of this part have been parsed.
     */
    protected $allChildrenParsed = false;

    /**
     * @var ParserPartProxy[] Parsed children used as 'first-in-first-out'
     *      stack as children are parsed.
     */
    protected $children = [];

    /**
     * @var ParserPartProxy Reference to the last child added to this part.
     */
    protected $lastAddedChild = null;

    /**
     * Ensures that the last child added to this part is fully parsed (content
     * and children).
     */
    protected function ensureLastChildParsed()
    {
        if ($this->lastAddedChild !== null) {
            $this->lastAddedChild->parseAll();
        }
    }

    /**
     * Parses the next child of this part and adds it to the children list.
     */
    protected function parseNextChild()
    {
        if ($this->allChildrenParsed) {
            return;
        }
        $this->parseContent();
        $this->ensureLastChildParsed();
        $next = $this->parser->parseNextChild($this);
        if ($next !== null) {
            array_push($this->children, $next);
            $this->lastAddedChild = $next;
        } else {
            $this->allChildrenParsed = true;
        }
    }

    /**
     * Returns the next child part if one exists, removing it from the internal
     * list of children, or null otherwise.
     *
     * @return IMessagePart|null the child part.
     */
    public function popNextChild()
    {
        if (empty($this->children)) {
            $this->parseNextChild();
        }
        $proxy = array_shift($this->children);
        return ($proxy !== null) ? $proxy->getPart() : null;
    }

    /**
     * Parses all content and children for this part.
     */
    public function parseAll()
    {
        $this->parseContent();
        $child = null;
        while (!$this->allChildrenParsed) {
            $this->parseNextChild();
        }
    }

    /**
     * Returns a ParameterHeader representing the parsed Content-Type header for
     * this part.
     *
     * @return \ZBateson\MailMimeParser\Header\ParameterHeader
     */
    public function getContentType()
    {
        return $this->getHeaderContainer()->get(HeaderConsts::CONTENT_TYPE);
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
     * Returns true if the passed $line of read input matches this part's mime
     * boundary, or any of its parent's mime boundaries for a multipart message.
     *
     * If the passed $line is the ending boundary for the current part,
     * $this->isEndBoundaryFound will return true after.
     *
     * @param string $line
     * @return bool
     */
    public function setEndBoundaryFound($line)
    {
        $boundary = $this->getMimeBoundary();
        if ($this->getParent() !== null && $this->getParent()->setEndBoundaryFound($line)) {
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
     * Returns true if the parser passed an input line to setEndBoundary that
     * matches a parent's mime boundary, and the following input belongs to a
     * new part under its parent.
     *
     * @return bool
     */
    public function isParentBoundaryFound()
    {
        return ($this->parentBoundaryFound);
    }

    /**
     * Returns true if an end boundary was found for this part.
     *
     * @return bool
     */
    public function isEndBoundaryFound()
    {
        return ($this->endBoundaryFound);
    }

    /**
     * Called once EOF is reached while reading content.  The method sets the
     * flag used by isParentBoundaryFound() to true on this part and all parent
     * parts.
     */
    public function setEof()
    {
        $this->parentBoundaryFound = true;
        if ($this->getParent() !== null) {
            $this->getParent()->setEof();
        }
    }

    public function setLastLineEndingLength($length)
    {
        $this->getParent()->setLastLineEndingLength($length);
    }

    public function getLastLineEndingLength()
    {
        return $this->getParent()->getLastLineEndingLength();
    }
}
