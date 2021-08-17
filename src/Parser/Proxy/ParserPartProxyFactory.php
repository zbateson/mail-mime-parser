<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Parser\IParser;
use ZBateson\MailMimeParser\Parser\PartBuilder;

/**
 * Responsible for creating proxied IUUEncodedPart instances wrapped in a
 * ParserPartProxy.
 *
 * @author Zaahid Bateson
 */
abstract class ParserPartProxyFactory
{
    /**
     * Constructs a new ParserPartProxy wrapping an IUUEncoded object.
     * 
     * @param PartBuilder $partBuilder
     * @return ParserPartProxy
     */
    abstract public function newInstance(PartBuilder $partBuilder, IParser $parser);
}
