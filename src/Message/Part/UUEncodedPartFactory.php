<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

/**
 * Description of UUEncodedPartFactory
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class UUEncodedPartFactory extends MessagePartFactory
{
    /**
     * Constructs a new UUEncodedPart object and returns it
     * 
     * @param resource $handle
     * @param resource $contentHandle
     * @param ZBateson\MailMimeParser\Message\Part\MessagePart[] $children
     * @param array $headers
     * @param array $properties
     * @return \ZBateson\MailMimeParser\Message\UUEncodedPartPart
     */
    public function newInstance(
        $handle,
        $contentHandle,
        array $children,
        array $headers,
        array $properties
    ) {
        return new UUEncodedPart(
            $handle,
            $contentHandle,
            $properties
        );
    }
}
