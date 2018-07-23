<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part\Factory;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message\Part\UUEncodedPart;
use ZBateson\MailMimeParser\Message\Part\PartBuilder;

/**
 * Responsible for creating UUEncodedPart instances.
 *
 * @author Zaahid Bateson
 */
class UUEncodedPartFactory extends MessagePartFactory
{
    /**
     * Constructs a new UUEncodedPart object and returns it
     * 
     * @param StreamInterface $messageStream
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\UUEncodedPartPart
     */
    public function newInstance(StreamInterface $messageStream, PartBuilder $partBuilder)
    {
        return new UUEncodedPart(
            $this->partStreamFilterManagerFactory->newInstance(),
            $this->streamFactory,
            $partBuilder,
            $this->streamFactory->getLimitedPartStream($messageStream, $partBuilder),
            $this->streamFactory->getLimitedContentStream($messageStream, $partBuilder)
        );
    }
}
