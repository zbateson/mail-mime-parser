<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Message\UUEncodedPart;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Parser\ParserProxy;
use ZBateson\MailMimeParser\Message\PartChildContained;

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
    public function newInstance(PartBuilder $partBuilder, ParsedPartChildrenContainer $parentContainer = null)
    {
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance();

        $part = new UUEncodedPart(
            ($parentContainer !== null) ? $parentContainer->getPartChildContained()->getPart() : null,
            $streamContainer
        );

        $parserProxy = new ParserProxy($this->baseParser, $this->streamFactory);
        $parserProxy->init($partBuilder, $streamContainer);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $streamContainer->setParsedStream($this->streamFactory->getLimitedPartStream($partBuilder->getStream(), $partBuilder));
        $part->attach($streamContainer);

        if ($parentContainer !== null) {
            $parentContainer->addParsedChild(new ParsedPartChildContained($part, null));
        }

        return $part;
    }
}
