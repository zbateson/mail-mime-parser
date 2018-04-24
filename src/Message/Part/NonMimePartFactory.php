<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\StreamWrapper;

/**
 * Responsible for creating NoneMimePart instances.
 *
 * @author Zaahid Bateson
 */
class NonMimePartFactory extends MessagePartFactory
{
    /**
     * Constructs a new NonMimePart object and returns it
     * 
     * @param StreamInterface $messageStream
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\Part\NonMimePart
     */
    public function newInstance(StreamInterface $messageStream, PartBuilder $partBuilder) {
        return new NonMimePart(
            $this->partStreamFilterManagerFactory->newInstance(),
            $this->streamDecoratorFactory->getLimitedPartStream($messageStream, $partBuilder),
            $this->streamDecoratorFactory->getLimitedContentStream($messageStream, $partBuilder)
        );
    }
}
