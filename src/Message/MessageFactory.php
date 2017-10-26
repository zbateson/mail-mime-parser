<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\Part\MimePartFactory;

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
     * @param resource $handle
     * @param resource $contentHandle
     * @param ZBateson\MailMimeParser\Message\Part\MessagePart[] $children
     * @param array $headers
     * @param array $properties
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function newInstance(
        $handle,
        $contentHandle,
        array $children,
        array $headers,
        array $properties
    ) {
        return new Message(
            $this->headerFactory,
            $handle,
            $contentHandle,
            $children,
            $headers
        );
    }
}
