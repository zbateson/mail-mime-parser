<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

/**
 * Description of IParser
 *
 * @author Zaahid Bateson
 */
interface IParser
{
    /**
     * Returns true if the passed $partBuilder is in a state currently
     * supported for parsing by this parser.
     *
     * @param PartBuilder $partBuilder
     * @return boolean
     */
    public function canParse(PartBuilder $partBuilder);
}
