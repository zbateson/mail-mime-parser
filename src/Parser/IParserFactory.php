<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;

/**
 * Description of IParserFactory
 *
 * @author Zaahid Bateson
 */
interface IParserFactory
{
    /**
     * @return IParser
     */
    public function newInstance();
    public function canParse(PartHeaderContainer $messageHeaders);
}
