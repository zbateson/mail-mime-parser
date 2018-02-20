<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

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
     * @param resource $handle
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\UUEncodedPartPart
     */
    public function newInstance($handle, PartBuilder $partBuilder)
    {
        $partStream = Psr7\stream_for($handle);
        $partLimitStream = new LimitStream($partStream, $partBuilder->getStreamPartLength(), $partBuilder->getStreamPartStartOffset());
        return new UUEncodedPart(
            StreamWrapper::getResource($partLimitStream),
            $partBuilder,
            $this->partStreamFilterManagerFactory->newInstance()
        );
    }
}
