<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use Pimple\Container as PimpleContainer;
use ZBateson\MailMimeParser\Container\AutoServiceContainer;

class ServiceLocator extends PimpleContainer
{
    private static $instance;

    public static function getSingleton() : ServiceLocator
    {
        if (self::$instance === null) {
            self::$instance = new AutoServiceContainer();
        }
        return self::$instance;
    }

    /**
     * Registers IExtension ServiceProviderInterfaces by calling self::register.
     * @param IExtension[] $extensions
     */
    public function registerExtensions(array $extensions) : self
    {
        foreach ($extensions as $e) {
            $this->register($e->getServiceProviderInterface());
        }
        return $this;
    }
}
