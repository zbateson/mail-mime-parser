<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\Part\PartBuilder;

/**
 * Description of MultipartParser
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class MultipartParser extends AbstractParser {

    protected function parse($handle, PartBuilder $partBuilder)
    {
        while (!$partBuilder->isParentBoundaryFound()) {
            $child = $this->partBuilderFactory->newPartBuilder(
                $this->messageService->getMimePartFactory()
            );
            $partBuilder->addChild($child);
            $this->invokeBaseParser($handle, $child);
        }
    }

    public function isSupported(PartBuilder $partBuilder)
    {
        return $partBuilder->isMultiPart();
    }
}
