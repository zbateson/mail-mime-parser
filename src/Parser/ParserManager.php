<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;

/**
 * Description of ParserManager
 *
 * @author Zaahid Bateson
 */
class ParserManager
{
    /**
     * @var IParser[]
     */
    protected $parsers = [];

    public function __construct(MimeParser $mimeParser, NonMimeParser $nonMimeParser)
    {
        $this->parsers = [ $mimeParser, $nonMimeParser ];
        $mimeParser->setParserManager($this);
        $nonMimeParser->setParserManager($this);
    }

    public function setParsers(array $parsers)
    {
        $this->parsers = $parsers;
    }

    /**
     * Adds an IParser at the highest priority (up front).
     *
     * @param IParser $pf
     */
    public function prependParser(IParser $parser)
    {
        array_unshift($this->parsers, $parser);
    }

    /**
     * Loops through registered IParsers and returns a parser that can
     * parse a part for the passed headers.
     *
     * @param PartHeaderContainer $container
     * @return IParser
     */
    public function createParserProxyFor(PartBuilder $partBuilder)
    {
        foreach ($this->parsers as $parser) {
            if ($parser->canParse($partBuilder)) {
                $factory = ($partBuilder->getParent() === null) ? 
                    $parser->getParserMessageProxyFactory() :
                    $parser->getParserPartProxyFactory();
                return $factory->newInstance($partBuilder, $parser);
            }
        }
        return null;
    }
}
