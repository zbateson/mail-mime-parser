<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

/**
 * Abstract factory for subclasses of MessagePart.
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
abstract class MessagePartFactory
{
    /**
     * Constructs a new MessagePart object and returns it
     * 
     * @param resource $handle
     * @param resource $contentHandle
     * @param ZBateson\MailMimeParser\Message\Part\MessagePart[] $children
     * @param array $headers
     * @param array $properties
     * @return \ZBateson\MailMimeParser\Message\Part\MessagePart
     */
    public abstract function newInstance(
        $handle,
        $contentHandle,
        array $children,
        array $headers,
        array $properties
    );
}
