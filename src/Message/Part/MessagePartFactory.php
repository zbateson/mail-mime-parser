<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Stream\StreamDecoratorFactory;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\PartFilterFactory;

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
     * Sets a cached singleton instance.
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
     * Returns a cached singleton instance if one exists, or null if one hasn't
     * been created yet.
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
     * @param HeaderFactory $hf
     * @param PartFilterFactory $pf
     * @return MessagePartFactory
     */
    public static function getInstance(
        StreamDecoratorFactory $sdf,
        PartStreamFilterManagerFactory $psf,
        HeaderFactory $hf = null,
        PartFilterFactory $pf = null
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
     * @param StreamInterface $messageStream
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\Part\MessagePart
     */
    public abstract function newInstance(StreamInterface $messageStream, PartBuilder $partBuilder);
}
