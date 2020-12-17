<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ReflectionClass;
use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message\MessageService;
use ZBateson\MailMimeParser\Message\PartFilterFactory;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Abstract factory for subclasses of MessagePart.
 *
 * @author Zaahid Bateson
 */
abstract class MessagePartFactory
{
    /**
     * @var StreamFactory the StreamFactory instance
     */
    protected $streamFactory;

    /**
     * @var MessagePartFactory[] cached instances of MessagePartFactory
     *      sub-classes
     */
    private static $instances = null;

    /**
     * Initializes class dependencies.
     *
     * @param StreamFactory $streamFactory
     */
    public function __construct(StreamFactory $streamFactory) {
        $this->streamFactory = $streamFactory;
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
     * @param StreamFactory $sdf
     * @param PartFilterFactory $pf
     * @param MessageService $ms
     * @return MessagePartFactory
     */
    public static function getInstance(
        StreamFactory $sdf,
        PartFilterFactory $pf = null,
        MessageService $ms = null
    ) {
        $instance = static::getCachedInstance();
        if ($instance === null) {
            $ref = new ReflectionClass(get_called_class());
            $n = $ref->getConstructor()->getNumberOfParameters();
            $args = [];
            for ($i = 0; $i < $n; ++$i) {
                $args[] = func_get_arg($i);
            }
            $instance = $ref->newInstanceArgs($args);
            static::setCachedInstance($instance);
        }
        return $instance;
    }

    /**
     * Constructs a new MessagePart object and returns it
     * 
     * @param PartBuilder $partBuilder
     * @param StreamInterface $messageStream
     * @return \ZBateson\MailMimeParser\Message\MessagePart
     */
    public abstract function newInstance(
        PartBuilder $partBuilder,
        StreamInterface $messageStream = null
    );
}