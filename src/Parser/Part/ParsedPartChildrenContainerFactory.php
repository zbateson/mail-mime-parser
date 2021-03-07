<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

/**
 * Description of ParsedPartChildrenContainerFactory
 *
 * @author Zaahid Bateson
 */
class ParsedPartChildrenContainerFactory
{
    public function newInstance()
    {
        return new ParsedPartChildrenContainer();
    }
}
