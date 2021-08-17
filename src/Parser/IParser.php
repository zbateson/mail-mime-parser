<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;

/**
 * Interface defining a message part parser.
 *
 * @author Zaahid Bateson
 */
interface IParser
{
    public function setParserManager(ParserManager $pm);
    public function canParse(PartBuilder $part);
    public function getParserMessageProxyFactory();
    public function getParserPartProxyFactory();

    /**
     *
     */
    public function parseContent(ParserPartProxy $proxy);

    /**
     * Parses
     *
     * @return IMessagePart|null
     */
    public function parseNextChild(ParserMimePartProxy $proxy);
}
