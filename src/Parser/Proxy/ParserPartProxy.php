<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Parser\IParser;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer;

/**
 * Base bi-directional proxy between a parser and a MessagePart.
 *
 * @author Zaahid Bateson
 */
class ParserPartProxy
{
    /**
     * @var IMessagePart The part.
     */
    protected $part;
    
    /**
     * @var IParser|null The parser.
     */
    protected $childParser;

    /**
     * @var PartBuilder The part's PartBuilder.
     */
    protected $partBuilder;

    /**
     * @var ParserPartProxy The parent parser proxy for this part.
     */
    protected $parent;

    /**
     * @var ParserPartStreamContainer The ParserPartStreamContainer used by the
     *      part.
     */
    protected $parserPartStreamContainer;

    public function __construct(
        PartBuilder $partBuilder,
        IParser $childParser = null,
        ParserMimePartProxy $parent = null
    ) {
        $this->partBuilder = $partBuilder;
        $this->childParser = $childParser;
        $this->parent = $parent;
    }

    /**
     * Sets the associated part.
     *
     * @param IMessagePart $part The part
     */
    public function setPart(IMessagePart $part)
    {
        $this->part = $part;
    }

    /**
     * Returns the IMessagePart associated with this proxy.
     *
     * @return IMessagePart the part.
     */
    public function getPart()
    {
        return $this->part;
    }

    /**
     * Sets the associated ParserPartStreamContainer.
     *
     * @param ParserPartStreamContainer $parserPartStreamContainer
     */
    public function setParserPartStreamContainer(ParserPartStreamContainer $parserPartStreamContainer)
    {
        $this->parserPartStreamContainer = $parserPartStreamContainer;
    }

    /**
     * Parses this part's content (if not already parsed).
     *
     * If the part has a parent, parseContent() will use
     * $this->parent->childParser, which is the matching type of parser for the
     * given part.  Otherwise, if it's the top-level part (Message), then
     * $this->childParser is used.
     */
    public function parseContent()
    {
        if (!$this->partBuilder->isContentParsed()) {
            $parser = ($this->parent === null) ? $this->childParser : $this->parent->childParser;
            $parser->parseContent($this);
        }
    }

    /**
     * Parses the associated part's content and children.
     */
    public function parseAll()
    {
        $this->parseContent();
    }

    /**
     * Returns the PartBuilder for this part.
     *
     * @return PartBuilder the associated PartBuilder.
     */
    public function getPartBuilder()
    {
        return $this->partBuilder;
    }
}
