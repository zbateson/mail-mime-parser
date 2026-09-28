<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Message;

/**
 * Tracks how many headers and header bytes may still be read, and how many
 * header tokens may still be parsed, across all the header containers of a
 * single message.
 *
 * @author Zaahid Bateson
 */
class HeaderBudget
{
    private int $remainingTokens;

    private int $remainingHeaders;

    private int $remainingBytes;

    public function __construct(
        private readonly int $maxTokenCount,
        private readonly int $maxHeaderCount,
        private readonly int $maxSizeBytes
    ) {
        $this->remainingTokens = $maxTokenCount;
        $this->remainingHeaders = $maxHeaderCount;
        $this->remainingBytes = $maxSizeBytes;
    }

    public function getMaxSizeBytes() : int
    {
        return $this->maxSizeBytes;
    }

    public function getRemainingBytes() : int
    {
        return $this->remainingBytes;
    }

    public function consumeBytes(int $count) : static
    {
        $this->remainingBytes = \max(0, $this->remainingBytes - $count);
        return $this;
    }

    public function getMaxTokenCount() : int
    {
        return $this->maxTokenCount;
    }

    public function getRemainingTokens() : int
    {
        return $this->remainingTokens;
    }

    public function consumeTokens(int $count) : static
    {
        $this->remainingTokens = \max(0, $this->remainingTokens - $count);
        return $this;
    }

    public function getMaxHeaderCount() : int
    {
        return $this->maxHeaderCount;
    }

    public function getRemainingHeaders() : int
    {
        return $this->remainingHeaders;
    }

    public function consumeHeaders(int $count) : static
    {
        $this->remainingHeaders = \max(0, $this->remainingHeaders - $count);
        return $this;
    }
}
