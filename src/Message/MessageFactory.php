<?php
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\Part\MimePartFactory;

/**
 * Description of MessageFactory
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class MessageFactory extends MimePartFactory
{
    public function newInstance(
        $handle,
        MimePart $parent,
        array $children,
        array $headers,
        array $properties
    ) {
        return new Message(
            $this->headerFactory,
            $handle,
            $children,
            $headers
        );
    }
}
