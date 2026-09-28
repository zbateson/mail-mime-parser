<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Message\Factory;

use Psr\Log\LoggerInterface;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\HeaderBudget;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;

/**
 * Creates PartHeaderContainer instances.
 *
 * @author Zaahid Bateson
 */
class PartHeaderContainerFactory
{
    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly HeaderFactory $headerFactory,
        protected readonly int $maxMessageHeaderTokenCount = 250000,
        protected readonly int $maxMessageHeaderCount = 50000,
        protected readonly int $maxMessageHeaderSizeBytes = 8388608
    ) {
    }

    /**
     * Creates and returns a PartHeaderContainer.
     *
     * A container cloned $from another shares its header budget.  Otherwise
     * the passed $budget is used, or a new one created if null.
     */
    public function newInstance(?PartHeaderContainer $from = null, ?HeaderBudget $budget = null) : PartHeaderContainer
    {
        if ($from === null && $budget === null) {
            $budget = new HeaderBudget(
                $this->maxMessageHeaderTokenCount,
                $this->maxMessageHeaderCount,
                $this->maxMessageHeaderSizeBytes
            );
        }
        return new PartHeaderContainer($this->logger, $this->headerFactory, $from, $budget);
    }
}
