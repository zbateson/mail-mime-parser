<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Part\ParsedMessageFactory;

/**
 * Top-level parser for parsing e-mail messages and parts.
 *
 * - holds sub-parsers
 * - proper place to perform any initial setup
 *
 * @author Zaahid Bateson
 */
class BaseParser
{
    protected $contentParsers = [];

    protected $childParsers = [];

    /**
     * Returns the array of content parsers.
     *
     * @return AbstractContentParser[]
     */
    public function getContentParsers()
    {
        return $this->contentParsers;
    }

    /**
     * Adds the passed $parser as a content parser.
     *
     * @param IContentParser $parser
     */
    public function addContentParser(IContentParser $parser, $position = null)
    {
        $pos = ($position === null || $position > count($this->contentParsers) || $position < 0) ?
            count($this->contentParsers) : $position;
        array_splice(
            $this->contentParsers,
            $pos,
            0,
            [ $parser ]
        );
    }

    /**
     * Adds the passed $parser as a child parser.
     *
     * @param IChildPartParser $parser
     */
    public function addChildParser(IChildPartParser $parser, $position = null)
    {
        $pos = ($position === null || $position > count($this->childParsers) || $position < 0) ?
            count($this->childParsers) : $position;
        array_splice(
            $this->childParsers,
            $pos,
            0,
            [ $parser ]
        );
    }

    public function parseContent(PartBuilder $partBuilder)
    {
        if ($partBuilder->isContentParsed()) {
            return;
        }
        foreach ($this->contentParsers as $p) {
            if ($p->canParse($partBuilder)) {
                $p->parseContent($partBuilder);
                return;
            }
        }
    }

    public function parseNextChild(PartBuilder $partBuilder)
    {
        $this->parseContent($partBuilder);
        foreach ($this->childParsers as $p) {
            if ($p->canParse($partBuilder)) {
                return $p->parseNextChild($partBuilder);
            }
        }
    }
}
