<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Parser\PartBuilder;

/**
 * Description of ParsedPartChildrenContainerFactory
 *
 * @author Zaahid Bateson
 */
class ParsedPartChildrenContainerFactory
{
    public function newInstance(PartBuilder $builder)
    {
        return new ParsedPartChildrenContainer($builder);
    }
}
