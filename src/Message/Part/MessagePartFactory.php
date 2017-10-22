<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

/**
 * Description of MessagePartFactory
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
abstract class MessagePartFactory
{
    /**
     * Constructs a new MessagePart object and returns it
     * 
     * @return \ZBateson\MailMimeParser\Message\Part\MessagePart
     */
    public abstract function newInstance(
        $handle,
        MimePart $parent,
        array $children,
        array $headers,
        array $properties
    );
}
