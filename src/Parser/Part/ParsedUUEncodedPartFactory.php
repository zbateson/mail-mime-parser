<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Message\UUEncodedPart;
use ZBateson\MailMimeParser\Parser\PartBuilder;

/**
 * Responsible for creating ParsedUUEncodedPart instances.
 *
 * @author Zaahid Bateson
 */
class ParsedUUEncodedPartFactory extends ParsedMessagePartFactory
{
    /**
     * Constructs a new UUEncodedPart object and returns it
     * 
     * @param PartBuilder $partBuilder
     * @return ParsedUUEncodedPart
     */
    public function newInstance(PartBuilder $partBuilder, IMimePart $parent = null)
    {
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance($partBuilder);

        $part = new UUEncodedPart(
            $parent,
            $streamContainer
        );

        $partBuilder->setContainers($streamContainer);
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $part->attach($streamContainer);
        return $part;
    }
}
