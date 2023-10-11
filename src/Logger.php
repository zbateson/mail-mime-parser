<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

/**
 *
 *
 * @author Zaahid Bateson
 */
class Logger implements ILogger
{
    private $logger;

    public function setLogger(LoggerInterface $logger) : ILogger
    {
        $this->logger = $logger;
        return $this;
    }

    protected function getLogger() : LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }
}
