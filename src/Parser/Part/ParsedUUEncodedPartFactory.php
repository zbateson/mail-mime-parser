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
     * @param StreamInterface $partStream
     * @return ParsedUUEncodedPart
     */
    public function newInstance(PartBuilder $partBuilder, StreamInterface $partStream = null)
    {
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance();
        if ($partStream !== null) {
            $streamContainer->setContentStream($this->streamFactory->getLimitedContentStream($partStream, $partBuilder));
        }
        $part = new UUEncodedPart(
            $streamContainer
        );
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $streamContainer->setParsedStream($partStream);
        $part->attach($streamContainer);
        return $part;
    }
}
