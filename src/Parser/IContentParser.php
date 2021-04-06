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
interface IContentParser extends IParser
{
    /**
     *
     */
    public function parseContent(PartBuilder $partBuilder);
}
