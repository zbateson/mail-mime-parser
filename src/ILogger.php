<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use Psr\Log\LoggerInterface;

/**
 * Interface for setting a logger on objects.
 *
 * Used by Container\LoggerServiceProvider to find and call setLogger on objects
 * implementing ILogger.
 *
 * @author Zaahid Bateson
 */
interface ILogger
{
    /**
     * Called by LoggerServiceProvider to set the global logger on an this
     * object.
     * 
     * @param LoggerInterface $logger
     * @return ILogger
     */
    public function setLogger(LoggerInterface $logger) : ILogger;
}
