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
 * Responsible for creating NoneMimePart instances.
 *
 * @author Zaahid Bateson
 */
class NonMimePartFactory extends MessagePartFactory
{
    /**
     * Constructs a new NonMimePart object and returns it
     * 
     * @param resource $handle
     * @param PartBuilder $partBuilder
     * @param PartStreamFilterManager $partStreamFilterManager
     * @return \ZBateson\MailMimeParser\Message\Part\NonMimePart
     */
    public function newInstance(
        $handle,
        PartBuilder $partBuilder
    ) {
        $partStream = Psr7\stream_for($handle);
        $partLimitStream = new LimitStream($partStream, $partBuilder->getStreamPartEnd() - $partBuilder->getStreamPartStart(), $partBuilder->getStreamPartStart());
        return new NonMimePart(
            StreamWrapper::getResource($partLimitStream),
            $partBuilder,
            $this->partStreamFilterManagerFactory->newInstance()
        );
    }
}
