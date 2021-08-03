<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Part\ParsedMessagePartFactory;
use Psr\Http\Message\StreamInterface;

/**
 * Responsible for creating PartBuilder instances.
 *
 * @author Zaahid Bateson
 */
class PartBuilderFactory
{
    /**
     * Constructs a new PartBuilder object and returns it
     * 
     * @param StreamInterface $messageStream
     * @return PartBuilder
     */
    public function newPartBuilder(StreamInterface $messageStream)
    {
        return new PartBuilder($messageStream);
    }

    /**
     * Constructs a new PartBuilder object and returns it
     *
     * @param ParsedMessagePartFactory $messagePartFactory
     * @param PartBuilder $parent
     * @return PartBuilder
     */
    public function newChildPartBuilder(PartBuilder $parent)
    {
        return new PartBuilder(
            null,
            $parent
        );
    }
}
