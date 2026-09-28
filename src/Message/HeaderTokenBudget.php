<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Message;

/**
 * Tracks how many header tokens may still be parsed across all the header
 * containers of a single message.
 *
 * @author Zaahid Bateson
 */
class HeaderTokenBudget
{
    private int $maxTokenCount;

    private int $remaining;

    public function __construct(int $maxTokenCount)
    {
        $this->maxTokenCount = $maxTokenCount;
        $this->remaining = $maxTokenCount;
    }

    public function getMaxTokenCount() : int
    {
        return $this->maxTokenCount;
    }

    public function getRemaining() : int
    {
        return $this->remaining;
    }

    public function consume(int $count) : static
    {
        $this->remaining = \max(0, $this->remaining - $count);
        return $this;
    }
}
