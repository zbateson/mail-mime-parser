<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Message\Factory;

use Psr\Log\LoggerInterface;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\HeaderTokenBudget;
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
        protected readonly int $maxMessageHeaderTokenCount = 250000
    ) {
    }

    /**
     * Creates and returns a PartHeaderContainer.
     *
     * A container cloned $from another shares its token budget.  Otherwise the
     * passed $tokenBudget is used, or a new one created if null.
     */
    public function newInstance(?PartHeaderContainer $from = null, ?HeaderTokenBudget $tokenBudget = null) : PartHeaderContainer
    {
        if ($from === null && $tokenBudget === null) {
            $tokenBudget = new HeaderTokenBudget($this->maxMessageHeaderTokenCount);
        }
        return new PartHeaderContainer($this->logger, $this->headerFactory, $from, $tokenBudget);
    }
}
