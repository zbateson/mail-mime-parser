<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;

/**
 * Description of ParsedPartChildrenContainerFactory
 *
 * @author Zaahid Bateson
 */
class ParsedPartChildrenContainerFactory
{
    public function newInstance(ParserMimePartProxy $parserProxy)
    {
        return new ParsedPartChildrenContainer($parserProxy);
    }
}
