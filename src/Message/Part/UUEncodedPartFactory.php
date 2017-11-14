<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

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
     * @param string $messageObjectId
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\UUEncodedPartPart
     */
    public function newInstance($messageObjectId, PartBuilder $partBuilder)
    {
        return new UUEncodedPart(
            $messageObjectId,
            $partBuilder,
            $this->partStreamFilterManagerFactory->newInstance()
        );
    }
}
