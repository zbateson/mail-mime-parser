<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message\UUEncodedPart;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Message\PartStreamContainer;

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
     * @return \ZBateson\MailMimeParser\Message\NonMimePart
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
        return $part;
    }
}
