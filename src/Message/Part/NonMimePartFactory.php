<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

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
     * @param string $messageObjectId
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\NonMimePart
     */
    public function newInstance($messageObjectId, PartBuilder $partBuilder)
    {
        return new NonMimePart($messageObjectId, $partBuilder);
    }
}
