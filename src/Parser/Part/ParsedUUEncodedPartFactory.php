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
use ZBateson\MailMimeParser\Parser\ParserProxy;

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
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance();

        $part = new UUEncodedPart(
            $parent,
            $streamContainer
        );

        $parserProxy = new ParserProxy($this->baseParser, $this->streamFactory);
        $parserProxy->init($partBuilder, $streamContainer, null);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $streamContainer->setParsedStream($this->streamFactory->getLimitedPartStream($partBuilder->getStream(), $partBuilder));
        $part->attach($streamContainer);

        return $part;
    }
}
