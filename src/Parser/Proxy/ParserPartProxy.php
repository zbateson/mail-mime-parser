<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Parser\IParser;
use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer;

/**
 * Description of ProxyPart
 *
 * @author Zaahid Bateson
 */
abstract class ParserPartProxy
{
    protected $partBuilder;
    protected $parser;
    protected $parent;

    protected $part;
    protected $parserPartStreamContainer;

    public function __construct(
        PartBuilder $partBuilder,
        IParser $parser,
        ParserMimePartProxy $parent = null
    ) {
        $this->partBuilder = $partBuilder;
        $this->parser = $parser;
        $this->parent = $parent;
    }

    public function setPart($part)
    {
        $this->part = $part;
    }

    public function setParserPartStreamContainer($parserPartStreamContainer)
    {
        $this->parserPartStreamContainer = $parserPartStreamContainer;
    }

    public function getParser()
    {
        return $this->parser;
    }

    public function parseContent()
    {
        if (!$this->partBuilder->isContentParsed()) {
            $this->parser->parseContent($this);
        }
    }

    public function parseAll()
    {
        $this->parseContent();
    }

    /**
     * Returns this ProxyPart's parent.
     *
     * @return ProxyPart
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     *
     * @return type
     */
    public function getPart()
    {
        return $this->part;
    }

    public function getPartBuilder()
    {
        return $this->partBuilder;
    }
}
