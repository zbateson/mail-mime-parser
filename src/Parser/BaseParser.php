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
    protected $headerParser;
    
    protected $contentParsers = [];

    protected $childParsers = [];

    public function __construct(HeaderParser $headerParser)
    {
        $this->headerParser = $headerParser;
    }

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
    public function addContentParser(IContentParser $parser)
    {
        $this->contentParsers[] = $parser;
    }

    /**
     * Adds the passed $parser as a child parser.
     *
     * @param IChildPartParser $parser
     */
    public function addChildParser(IChildPartParser $parser)
    {
        $this->childParsers[] = $parser;
    }

    public function parseHeaders(PartBuilder $partBuilder)
    {
        $partBuilder->setStreamPartStartPos($partBuilder->getMessageResourceHandlePos());
        $this->headerParser->parse($partBuilder);
    }

    public function parseContent(PartBuilder $partBuilder, ParserProxy $proxy)
    {
        foreach ($this->contentParsers as $p) {
            if ($p->canParse($partBuilder)) {
                $p->parseContent($partBuilder, $proxy);
                return;
            }
        }
    }

    public function parseNextChild(PartBuilder $partBuilder, ParserProxy $proxy)
    {
        if (!$partBuilder->isContentParsed()) {
            $this->parseContent($partBuilder, $proxy);
        }
        foreach ($this->childParsers as $p) {
            if ($p->canParse($partBuilder)) {
                return $p->parseNextChild($partBuilder, $proxy);
            }
        }
    }
}
