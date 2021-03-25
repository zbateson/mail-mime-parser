<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\PartChildContained;

/**
 * Description of ParsedPartChildContained
 *
 * @author Zaahid Bateson
 */
class ParsedPartChildContained extends PartChildContained
{
    public function ensurePartParsed()
    {
        if ($this->container !== null && $this->container instanceof ParsedPartChildrenContainer) {
            $this->container->ensurePartParsed();
        } else {
            $this->part->hasContent();
        }
    }
}
