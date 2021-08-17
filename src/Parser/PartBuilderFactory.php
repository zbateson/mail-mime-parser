<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;
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
    public function newPartBuilder(PartHeaderContainer $headerContainer, StreamInterface $messageStream)
    {
        return new PartBuilder($headerContainer, $messageStream);
    }

    /**
     * Constructs a new PartBuilder object and returns it
     *
     * @param ParserPartProxy $parent
     * @return PartBuilder
     */
    public function newChildPartBuilder(PartHeaderContainer $headerContainer, ParserPartProxy $parent)
    {
        return new PartBuilder(
            $headerContainer,
            null,
            $parent
        );
    }
}
