<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;

/**
 * Description of IParser
 *
 * @author Zaahid Bateson
 */
interface IParser
{
    /**
     *
     * @return IMessagePart|null
     */
    public function parseNextChild(ParserMimePartProxy $proxy);

    /**
     *
     */
    public function parseContent(ParserPartProxy $proxy);
}
