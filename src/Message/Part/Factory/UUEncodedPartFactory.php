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
use ZBateson\MailMimeParser\Message\Part\PartStreamContainer;

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
     * @param PartBuilder $partBuilder
     * @param StreamInterface $messageStream
     * @return \ZBateson\MailMimeParser\Message\Part\NonMimePart
     */
    public function newInstance(PartBuilder $partBuilder, StreamInterface $messageStream = null)
    {
        $streamContainer = new PartStreamContainer($this->streamFactory);
        if ($messageStream !== null) {
            $streamContainer->setStream($this->streamFactory->getLimitedPartStream($messageStream, $partBuilder));
            $streamContainer->setContentStream($this->streamFactory->getLimitedContentStream($messageStream, $partBuilder));
        }
        $part = new UUEncodedPart(
            $this->streamFactory
        );
        $part->initFrom($partBuilder, $streamContainer);
        return $part;
    }
}
