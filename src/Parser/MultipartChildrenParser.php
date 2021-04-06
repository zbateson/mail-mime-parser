<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Part\ParsedMimePartFactory;

/**
 * Creates and adds PartBuilder children to a PartBuilder with a multipart mime
 * type, invoking the base parser on each child to read it.
 *
 * @author Zaahid Bateson
 */
class MultipartChildrenParser implements IChildPartParser
{
    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @var ParsedMimePartFactory for ParsedMimePart objects
     */
    protected $parsedMimePartFactory;

    /**
     * @var BaseParser
     */
    protected $baseParser;

    public function __construct(
        PartBuilderFactory $pbf,
        BaseParser $parser,
        ParsedMimePartFactory $f
    ) {
        $this->partBuilderFactory = $pbf;
        $this->baseParser = $parser;
        $this->parsedMimePartFactory = $f;
    }

    /**
     * Checks if the new child part is just content past the end boundary
     *
     * @param ParserProxy $proxy
     * @param PartBuilder $parent
     * @param PartBuilder $child
     */
    private function createPart(PartBuilder $parent, PartBuilder $child)
    {
        $part = $child->createMessagePart();
        if ($parent->isEndBoundaryFound()) {
            // read the content and discard
            $part->hasContent();
        }
    }

    /**
     * Returns true if there are more parts
     * 
     * @param PartBuilder $partBuilder
     * @return boolean
     */
    public function parseNextChild(PartBuilder $partBuilder)
    {
        if ($partBuilder->isParentBoundaryFound()) {
            return false;
        }
        $child = $this->partBuilderFactory->newChildPartBuilder(
            $this->parsedMimePartFactory,
            $partBuilder
        );
        $this->baseParser->parseHeaders($child);
        $this->createPart($partBuilder, $child);
        return !$partBuilder->isParentBoundaryFound();
    }

    public function canParse(PartBuilder $partBuilder)
    {
        return $partBuilder->isMultiPart();
    }
}
