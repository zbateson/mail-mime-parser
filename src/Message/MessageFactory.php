<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\Part\MimePartFactory;
use ZBateson\MailMimeParser\Message\Part\PartBuilder;

/**
 * Responsible for creating Message instances.
 *
 * @author Zaahid Bateson
 */
class MessageFactory extends MimePartFactory
{
    /**
     * Constructs a new Message object and returns it
     * 
     * @param string $messageObjectId
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function newInstance($messageObjectId, PartBuilder $partBuilder)
    {
        return new Message(
            $this->headerFactory,
            $this->partFilterFactory,
            $messageObjectId,
            $partBuilder
        );
    }
}
