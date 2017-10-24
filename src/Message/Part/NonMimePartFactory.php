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
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class NonMimePartFactory extends MessagePartFactory
{
    /**
     * Constructs a new NonMimePart object and returns it
     * 
     * @return \ZBateson\MailMimeParser\Message\NonMimePart
     */
    public function newInstance(
        $handle,
        $contentHandle,
        MimePart $parent,
        array $children,
        array $headers,
        array $properties
    ) {
        return new NonMimePart(
            $handle,
            $contentHandle,
            $parent
        );
    }
}
