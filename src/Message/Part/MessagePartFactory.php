<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\Header\HeaderFactory;

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
     * @return MessagePartFactory
     */
    public static function getInstance(HeaderFactory $hf = null)
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
     * @param resource $handle
     * @param resource $contentHandle
     * @param ZBateson\MailMimeParser\Message\Part\MessagePart[] $children
     * @param array $headers
     * @param array $properties
     * @return \ZBateson\MailMimeParser\Message\Part\MessagePart
     */
    public abstract function newInstance(
        $handle,
        $contentHandle,
        array $children,
        array $headers,
        array $properties
    );
}
