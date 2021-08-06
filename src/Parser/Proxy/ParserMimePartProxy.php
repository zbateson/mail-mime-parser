<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Parser\IParser;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainer;

/**
 * Description of MimePartProxy
 *
 * @author Zaahid Bateson
 */
class ParserMimePartProxy extends ParserPartProxy
{
    /**
     * @var PartHeaderContainer
     */
    protected $headerContainer;

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

    protected $lastAddedChild = null;

    protected $parserPartChildrenContainer = null;

    public function __construct(
        PartHeaderContainer $headerContainer,
        PartBuilder $partBuilder,
        IParser $parser,
        ParserPartProxy $parent = null
    ) {
        parent::__construct($partBuilder, $parser, $parent);
        $this->headerContainer = $headerContainer;
    }

    public function setParserPartChildrenContainer(ParserPartChildrenContainer $parserPartChildrenContainer)
    {
        $this->parserPartChildrenContainer = $parserPartChildrenContainer;
    }

    protected function ensureLastChildRead()
    {
        if ($this->lastAddedChild !== null) {
            $this->lastAddedChild->parseAll();
        }
    }

    public function parseNextChild()
    {
        $this->ensureLastChildRead();
        $this->parseContent();
        return $this->parser->parseNextChild($this);
    }

    public function parseAll()
    {
        $this->parseContent();
        $child = null;
        do {
            $child = $this->parseNextChild();
        } while ($child !== null);
    }

    public function addChild(ParserPartProxy $child)
    {
        $this->parserPartChildrenContainer->add($child->getPart());
        $this->lastAddedChild = $child;
    }

    public function getHeaderContainer()
    {
        return $this->headerContainer;
    }

    /**
     * Returns a ParameterHeader representing the parsed Content-Type header for
     * this PartBuilder.
     *
     * @return \ZBateson\MailMimeParser\Header\ParameterHeader
     */
    public function getContentType()
    {
        return $this->headerContainer->get(HeaderConsts::CONTENT_TYPE);
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

    /**
     *
     * @return type
     */
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
}
