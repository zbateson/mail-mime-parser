<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\PartFilterFactory;
use ReflectionClass;

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
     * Initializes class dependencies.
     * 
     * @param PartStreamFilterManagerFactory $psf
     */
    public function __construct(PartStreamFilterManagerFactory $psf)
    {
        $this->partStreamFilterManagerFactory = $psf;
    }
    
    /**
     * Returns the singleton instance for the class.
     * 
     * @param PartStreamFilterManagerFactory $psf
     * @param HeaderFactory $hf
     * @param PartFilterFactory $pf
     * @return MessagePartFactory
     */
    public static function getInstance(
        PartStreamFilterManagerFactory $psf,
        HeaderFactory $hf = null,
        PartFilterFactory $pf = null
    ) {
        static $instances = [];
        $class = get_called_class();
        if (!isset($instances[$class])) {
            $rf = new ReflectionClass($class);
            $constr = $rf->getConstructor();
            if ($constr->getNumberOfParameters() === 3) {
                $instances[$class] = new static($psf, $hf, $pf);
            } else {
                $instances[$class] = new static($psf);
            }
        }
        return $instances[$class];
    }
    
    /**
     * Constructs a new MessagePart object and returns it
     * 
     * @param string $messageObjectId
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\Part\MessagePart
     */
    public abstract function newInstance($messageObjectId, PartBuilder $partBuilder);
}
