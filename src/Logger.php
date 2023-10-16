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
 * Default ILogger implementation also defines a protected 'getLogger' function
 * for sub-classes.
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

    /**
     * Returns the set LoggerInterface, or a Psr\Log\NullLogger if none set.
     *
     * @return LoggerInterface
     */
    protected function getLogger() : LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }
}
