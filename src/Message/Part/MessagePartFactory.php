<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

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
     * Setting default constructor visibility to 'protected'.
     */
    protected function __construct()
    {
    }
    
    /**
     * Returns the singleton instance for the class.
     * 
     * @param HeaderFactory $hf
     * @param PartFilterFactory $pf
     * @return MessagePartFactory
     */
    public static function getInstance(HeaderFactory $hf = null, PartFilterFactory $pf = null)
    {
        static $instances = [];
        $class = get_called_class();
        if (!isset($instances[$class])) {
            $instances[$class] = new static();
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
