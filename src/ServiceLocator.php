<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use ZBateson\MailMimeParser\Container\AutoServiceContainer;
use ZBateson\MailMimeParser\Container\LoggerServiceProvider;

/**
 * 
 *
 * @author Zaahid Bateson
 */
class ServiceLocator extends Container
{
    /**
     * @var AutoServiceContainer the singleton instance
     */
    private static $instance;

    /**
     * @var ServiceProviderInterface[] global extensions activated on $instance
     */
    private static $serviceProviders;

    /**
     * @var LoggerInterface the global logger
     */
    private static $logger;

    protected function __construct(?LoggerInterface $logger = null, ?array $serviceProviders = null)
    {
        parent::__construct([ LoggerInterface::class => $logger ?? new NullLogger() ]);
        $this->registerAll($serviceProviders);
    }

    /**
     * Returns the global container instance.  Note that this isn't technically
     * a singleton since the returned instance may be different if calling
     * setGlobalServiceProviders/setGlobalLogger between calls.
     *
     * @return ServiceLocator
     */
    public static function getGlobalInstance() : ServiceLocator
    {
        if (self::$instance === null) {
            self::$instance = new AutoServiceContainer(self::$logger, self::$serviceProviders);
        }
        return self::$instance;
    }

    /**
     * Creates a new instance, used by MailMimeParser if providing a logger or
     * array of service providers to __construct directly.
     *
     * @param ?LoggerInterface $logger
     * @param ?ServiceProviderInterface[] $serviceProviders
     * @return ServiceLocator
     */
    public static function newInstance(?LoggerInterface $logger = null, ?array $serviceProviders = null) : ServiceLocator
    {
        return new AutoServiceContainer($logger, $serviceProviders);
    }

    /**
     * Registers global services provider extensions.
     * 
     * Note: if a global instance was requested prior to calling, the global
     * instance will be set to null and recreated upon calling getGlobalInstance
     * since these need to be setup first.
     *
     * @param ServiceProviderInterface[] $sps
     */
    public static function setGlobalServiceProviders(array $sps) : void
    {
        self::$serviceProviders = $sps;
        self::$instance = null;
    }

    /**
     * Sets the global logger.
     *
     * Note: if a global instance was requested prior to calling, the global
     * instance will be set to null and recreated upon calling getGlobalInstance
     * since this needs to be setup first.
     *
     * @param LoggerInterface $logger
     */
    public static function setGlobalLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;
        self::$instance = null;
    }

    /**
     * Convenience method to register an array of ServiceProviderInterface
     * extensions (calls $this->register() on each one).
     *
     * @param ServiceProviderInterface[]|null $exts
     */
    private function registerAll(?array $exts)
    {
        $a = array_merge($exts ?? [], [ new LoggerServiceProvider() ]);
        foreach ($a as $e) {
            $this->register($e);
        }
    }
}
