<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\Stream\StreamDecoratorFactory;

/**
 * Abstract factory for subclasses of MessagePart.
 *
 * @author Zaahid Bateson
 */
abstract class MessagePartFactory
{
    /**
     * @var PartStreamFilterManagerFactory responsible for creating
     *      PartStreamFilterManager instances
     */
    protected $partStreamFilterManagerFactory;

    /**
     * @var StreamDecoratorFactory the StreamDecoratorFactory instance
     */
    protected $streamDecoratorFactory;

    /**
     * @static MessagePartFactory[] cached instances of MessagePartFactory
     *      sub-classes
     */
    private static $instances = null;

    /**
     * Initializes class dependencies.
     *
     * @param StreamDecoratorFactory $streamDecoratorFactory
     * @param PartStreamFilterManagerFactory $psf
     */
    public function __construct(
        StreamDecoratorFactory $streamDecoratorFactory,
        PartStreamFilterManagerFactory $psf
    ) {
        $this->streamDecoratorFactory = $streamDecoratorFactory;
        $this->partStreamFilterManagerFactory = $psf;
    }
    
    /**
     * 
     * @param MessagePartFactory $instance
     */
    protected static function setCachedInstance(MessagePartFactory $instance)
    {
        if (self::$instances === null) {
            self::$instances = [];
        }
        $class = get_called_class();
        self::$instances[$class] = $instance;
    }

    /**
     * 
     * @return MessagePartFactory
     */
    protected static function getCachedInstance()
    {
        $class = get_called_class();
        if (self::$instances === null || !isset(self::$instances[$class])) {
            return null;
        }
        return self::$instances[$class];
    }

    /**
     * Returns the singleton instance for the class.
     *
     * @param StreamDecoratorFactory $sdf
     * @param PartStreamFilterManagerFactory $psf
     * @return MessagePartFactory
     */
    public static function getInstance(
        StreamDecoratorFactory $sdf,
        PartStreamFilterManagerFactory $psf
    ) {
        $instance = static::getCachedInstance();
        if ($instance === null) {
            $instance = new static($sdf, $psf);
            static::setCachedInstance($instance);
        }
        return $instance;
    }
    
    /**
     * Constructs a new MessagePart object and returns it
     * 
     * @param resource $handle
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\Part\MessagePart
     */
    public abstract function newInstance($handle, PartBuilder $partBuilder);
}
