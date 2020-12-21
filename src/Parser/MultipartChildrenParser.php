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
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class MultipartChildrenParser extends AbstractParser
{
    /**
     * @var ParsedMimePartFactory for ParsedMimePart objects
     */
    protected $parsedMimePartFactory;

    public function __construct(
        PartBuilderFactory $pbf,
        ParsedMimePartFactory $f
    ) {
        parent::__construct($pbf);
        $this->parsedMimePartFactory = $f;
    }

    protected function parse($handle, PartBuilder $partBuilder)
    {
        while (!$partBuilder->isParentBoundaryFound()) {
            $child = $this->partBuilderFactory->newPartBuilder(
                $this->parsedMimePartFactory
            );
            $partBuilder->addChild($child);
            $this->invokeBaseParser($handle, $child);
        }
    }

    protected function isSupported(PartBuilder $partBuilder)
    {
        return $partBuilder->isMultiPart();
    }
}
