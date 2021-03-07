<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

/**
 * 
 * @author Zaahid Bateson
 */
interface IChildPartParser extends IParser
{
    /**
     *
     * @return bool true if there are more children
     */
    public function parseNextChild(PartBuilder $partBuilder, ParserProxy $proxy);
}
