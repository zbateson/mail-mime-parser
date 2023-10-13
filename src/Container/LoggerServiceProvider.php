<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Container;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Log\LoggerInterface;
use ZBateson\MailMimeParser\ILogger;

/**
 * @author Zaahid Bateson
 */
class LoggerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        if ($pimple instanceof AutoServiceContainer) {
            $pimple->addGlobalExtension(function($ob, $container) {
                if ($ob instanceof ILogger) {
                    $ob->setLogger($container[LoggerInterface::class]);
                }
                return $ob;
            });
        }
    }
}
