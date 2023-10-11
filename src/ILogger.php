<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use Psr\Log\LoggerInterface;

/**
 *
 *
 * @author Zaahid Bateson
 */
interface ILogger
{
    public function setLogger(LoggerInterface $logger) : ILogger;
}
