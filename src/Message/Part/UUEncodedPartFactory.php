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
            StreamWrapper::getResource($this->streamDecoratorFactory->getLimitedPartStream($messageStream, $partBuilder)),
            $partBuilder,
            $this->partStreamFilterManagerFactory->newInstance()
        );
    }
}
