<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Parser\Proxy;

use Psr\Log\LogLevel;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\ParameterHeader;
use ZBateson\MailMimeParser\Message\IMessagePart;

/**
 * A bi-directional parser-to-part proxy for MimeParser and IMimeParts.
 *
 * @author Zaahid Bateson
 */
class ParserMimePartProxy extends ParserPartProxy
{
    /**
     * @var bool set to true once the end boundary of the currently-parsed
     *      part is found.
     */
    protected bool $endBoundaryFound = false;

    /**
     * @var bool set to true once a boundary belonging to this parent's part
     *      is found.
     */
    protected bool $parentBoundaryFound = false;

    /**
     * @var bool true once all children of this part have been parsed.
     */
    protected bool $allChildrenParsed = false;

    /**
     * @var ParserPartProxy[] Array of all parsed children.
     */
    protected array $children = [];

    /**
     * @var ParserPartProxy[] Parsed children used as a 'first-in-first-out'
     *      stack as children are parsed.
     */
    protected array $childrenStack = [];

    /**
     * @var ParserPartProxy Reference to the last child added to this part.
     */
    protected ?ParserPartProxy $lastAddedChild = null;

    /**
     * @var ?string NULL if the current part does not have a boundary, and
     *      otherwise contains the value of the boundary parameter of the
     *      content-type header if the part contains one.
     */
    private ?string $mimeBoundary = null;

    /**
     * @var bool FALSE if not queried for in the content-type header of this
     *      part and set in $mimeBoundary.
     */
    private bool $mimeBoundaryQueried = false;

    /**
     * @var string[]|null this part's own boundary lines ('--boundary' and
     *      '--boundary--'), empty if it has no boundary, null until first
     *      needed.  Registered in the root part's $liveBoundaryLines while
     *      this part is being parsed.
     */
    private ?array $ownBoundaryLines = null;

    /**
     * @var array<string, int> used on the root part only: the boundary lines
     *      of every part still being parsed, mapped to how many such parts own
     *      them, so a body line matching none of them is rejected without
     *      walking the parent chain.
     */
    private array $liveBoundaryLines = [];

    /**
     * @var ?ParserMimePartProxy the topmost part of the message, resolved on
     *      first use.
     */
    private ?ParserMimePartProxy $root = null;

    /**
     * @var ?ParserMimePartProxy the topmost ancestor, which keeps the last
     *      line ending length for the whole message, resolved on first use.
     */
    private ?ParserMimePartProxy $topParent = null;

    /**
     * Ensures that the last child added to this part is fully parsed (content
     * and children).
     */
    protected function ensureLastChildParsed() : static
    {
        if ($this->lastAddedChild !== null) {
            $this->lastAddedChild->parseAll();
        }
        return $this;
    }

    /**
     * Parses the next child of this part and adds it to the 'stack' of
     * children.
     */
    protected function parseNextChild() : static
    {
        if ($this->allChildrenParsed) {
            return $this;
        }
        $this->parseContent();
        $this->ensureLastChildParsed();
        $next = $this->parser->parseNextChild($this);
        if ($next !== null) {
            $this->children[] = $next;
            $this->childrenStack[] = $next;
            $this->lastAddedChild = $next;
        } else {
            $this->allChildrenParsed = true;
        }
        return $this;
    }

    /**
     * Returns the next child part if one exists, popping it from the internal
     * 'stack' of children, attempting to parse a new one if the stack is empty,
     * and returning null if there are no more children.
     *
     * @return ?IMessagePart the child part.
     */
    public function popNextChild() : ?IMessagePart
    {
        if (empty($this->childrenStack)) {
            $this->parseNextChild();
        }
        $proxy = \array_shift($this->childrenStack);
        return ($proxy !== null) ? $proxy->getPart() : null;
    }

    /**
     * Parses all content and children for this part.
     */
    public function parseAll() : static
    {
        $this->parseContent();
        while (!$this->allChildrenParsed) {
            $this->parseNextChild();
        }
        return $this;
    }

    /**
     * Returns a ParameterHeader representing the parsed Content-Type header for
     * this part.
     */
    public function getContentType() : ?ParameterHeader
    {
        $header = $this->getHeaderContainer()->get(HeaderConsts::CONTENT_TYPE);
        if ($header === null) {
            return null;
        }
        \assert($header instanceof ParameterHeader);
        return $header;
    }

    /**
     * Returns the parsed boundary parameter of the Content-Type header if set
     * for a multipart message part.
     *
     */
    public function getMimeBoundary() : ?string
    {
        if ($this->mimeBoundaryQueried === false) {
            $this->mimeBoundaryQueried = true;
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
     */
    public function setEndBoundaryFound(string $line) : bool
    {
        $this->getOwnBoundaryLines();
        if (!isset($this->getRoot()->liveBoundaryLines[$line])) {
            return false;
        }
        // only this part and its ancestors are still being parsed, so the line
        // belongs to one of them, and an outer part's boundary wins
        $chain = [];
        for ($proxy = $this; $proxy instanceof ParserMimePartProxy; $proxy = $proxy->getParent()) {
            $chain[] = $proxy;
        }
        for ($i = \count($chain) - 1; $i >= 0; --$i) {
            $owner = $chain[$i];
            $index = \array_search($line, $owner->getOwnBoundaryLines(), true);
            if ($index === false) {
                continue;
            }
            for ($j = 0; $j < $i; ++$j) {
                $chain[$j]->parentBoundaryFound = true;
                $chain[$j]->releaseBoundaryLines();
            }
            if ($index === 1) {
                $owner->endBoundaryFound = true;
                $owner->releaseBoundaryLines();
            }
            return true;
        }
        return false;
    }

    private function getRoot() : ParserMimePartProxy
    {
        if ($this->root === null) {
            $root = $this;
            for ($next = $root->getParent(); $next instanceof ParserMimePartProxy; $next = $next->getParent()) {
                $root = $next;
            }
            $this->root = $root;
        }
        return $this->root;
    }

    /**
     * Returns this part's boundary lines, registering them as live in the root
     * part on first use.
     *
     * @return string[]
     */
    private function getOwnBoundaryLines() : array
    {
        if ($this->ownBoundaryLines === null) {
            $parent = $this->getParent();
            if ($parent instanceof ParserMimePartProxy) {
                $parent->getOwnBoundaryLines();
            }
            $boundary = $this->getMimeBoundary();
            $this->ownBoundaryLines = ($boundary !== null) ? ["--$boundary", "--$boundary--"] : [];
            $live = &$this->getRoot()->liveBoundaryLines;
            foreach ($this->ownBoundaryLines as $line) {
                $live[$line] = ($live[$line] ?? 0) + 1;
            }
        }
        return $this->ownBoundaryLines;
    }

    /**
     * Called once this part is finished parsing, so its boundary lines no
     * longer count as live.
     */
    private function releaseBoundaryLines() : void
    {
        if (!empty($this->ownBoundaryLines)) {
            $live = &$this->getRoot()->liveBoundaryLines;
            foreach ($this->ownBoundaryLines as $line) {
                if (isset($live[$line]) && --$live[$line] <= 0) {
                    unset($live[$line]);
                }
            }
            $this->ownBoundaryLines = [];
        }
    }

    /**
     * Returns true if the parser passed an input line to setEndBoundary that
     * matches a parent's mime boundary, and the following input belongs to a
     * new part under its parent.
     *
     */
    public function isParentBoundaryFound() : bool
    {
        return ($this->parentBoundaryFound);
    }

    /**
     * Returns true if an end boundary was found for this part.
     *
     */
    public function isEndBoundaryFound() : bool
    {
        return ($this->endBoundaryFound);
    }

    /**
     * Called once EOF is reached while reading content.  The method sets the
     * flag used by isParentBoundaryFound() to true on this part and all parent
     * parts.
     *
     */
    public function setEof() : static
    {
        $this->parentBoundaryFound = true;
        $this->releaseBoundaryLines();
        if ($this->getParent() !== null) {
            $this->getParent()->setEof();
        }
        return $this;
    }

    /**
     * Overridden to set a 0-length content length, and a stream end pos of -2
     * if the passed end pos is before the start pos (can happen if a mime
     * end boundary doesn't have an empty line before the next parent start
     * boundary).
     */
    public function setStreamPartAndContentEndPos(int $streamContentEndPos) : static
    {
        // check if we're expecting a boundary and didn't find one
        if (!$this->endBoundaryFound && !$this->parentBoundaryFound) {
            if (!empty($this->mimeBoundary) || !empty($this->getParent()->mimeBoundary)) {
                $this->addError('End boundary for part not found', LogLevel::WARNING);
            }
        }
        $start = $this->getStreamContentStartPos();
        if ($streamContentEndPos - $start < 0) {
            parent::setStreamPartAndContentEndPos($start);
            $this->setStreamPartEndPos($streamContentEndPos);
        } else {
            parent::setStreamPartAndContentEndPos($streamContentEndPos);
        }
        return $this;
    }

    /**
     * Returns the topmost ancestor of this part, normally the
     * ParserMessageProxy, which is what stores the last line ending length.
     */
    private function getTopParent() : ParserMimePartProxy
    {
        if ($this->topParent === null) {
            $top = $this->getParent();
            \assert($top instanceof ParserMimePartProxy);
            for ($next = $top->getParent(); $next instanceof ParserMimePartProxy; $next = $next->getParent()) {
                $top = $next;
            }
            $this->topParent = $top;
        }
        return $this->topParent;
    }

    /**
     * Sets the length of the last line ending read by MimeParser (e.g. 2 for
     * '\r\n', or 1 for '\n').
     *
     * The line ending may not belong specifically to this part, so
     * ParserMimePartProxy simply calls setLastLineEndingLength on its topmost
     * parent, a ParserMessageProxy which actually stores the length.
     */
    public function setLastLineEndingLength(int $length) : static
    {
        $this->getTopParent()->setLastLineEndingLength($length);
        return $this;
    }

    /**
     * Returns the length of the last line ending read by MimeParser (e.g. 2 for
     * '\r\n', or 1 for '\n').
     *
     * The line ending may not belong specifically to this part, so
     * ParserMimePartProxy simply calls getLastLineEndingLength on its topmost
     * parent, a ParserMessageProxy which actually keeps the length and
     * returns it.
     *
     * @return int the length of the last line ending read
     */
    public function getLastLineEndingLength() : int
    {
        return $this->getTopParent()->getLastLineEndingLength();
    }

    /**
     * Returns the number of parts created so far while parsing the message
     * this part belongs to.
     *
     * The count is message-wide, so ParserMimePartProxy simply calls
     * getPartCount() on its parent, which must eventually reach a
     * ParserMessageProxy which actually keeps the count and returns it.
     *
     * @return int the number of parts created for this message so far
     */
    public function getPartCount() : int
    {
        return $this->getParent()->getPartCount();
    }

    /**
     * Increments the number of parts created while parsing the message this
     * part belongs to.
     *
     * The count is message-wide, so ParserMimePartProxy simply calls
     * incrementPartCount() on its parent, which must eventually reach a
     * ParserMessageProxy which actually stores the count.
     */
    public function incrementPartCount() : static
    {
        $this->getParent()->incrementPartCount();
        return $this;
    }

    /**
     * Returns the last part that was added.
     */
    public function getLastAddedChild() : ?ParserPartProxy
    {
        return $this->lastAddedChild;
    }

    /**
     * Returns the added child at the provided index, useful for looking at
     * previously parsed children.
     */
    public function getAddedChildAt(int $index) : ?ParserPartProxy
    {
        return $this->children[$index] ?? null;
    }
}
