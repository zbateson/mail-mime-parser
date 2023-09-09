<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;

/**
 * Reads a generic header.
 *
 * Header's may contain mime-encoded parts, quoted parts, and comments.  The
 * string value is the combined value of all its parts.
 *
 * @author Zaahid Bateson
 */
class GenericHeader extends AbstractHeader
{
    public function getValue() : ?string
    {
        if (!empty($this->parts)) {
            return \implode('', \array_map(function($p) { return $p->getValue(); }, $this->parts));
        }
        return null;
    }

    /**
     * Returns a GenericConsumer.
     *
     * @return Consumer\AbstractConsumer
     */
    protected function getConsumer(ConsumerService $consumerService)
    {
        return $consumerService->getGenericConsumer();
    }
}
