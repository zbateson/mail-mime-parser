<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part\Factory;

use ZBateson\MailMimeParser\Stream\StreamDecoratorFactory;
use ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager;

/**
 * Responsible for creating PartStreamFilterManager instances.
 *
 * @author Zaahid Bateson
 */
class PartStreamFilterManagerFactory
{
    /**
     * @var StreamDecoratorFactory the StreamDecoratorFactory needed to
     *      initialize a new PartStreamFilterManager.
     */
    protected $streamDecoratorFactory;
    
    /**
     * Initializes dependencies
     *
     * @param StreamDecoratorFactory $streamDecoratorFactory
     */
    public function __construct(StreamDecoratorFactory $streamDecoratorFactory) {
        $this->streamDecoratorFactory = $streamDecoratorFactory;
    }
    
    /**
     * Constructs a new PartStreamFilterManager object and returns it.
     * 
     * @return \ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager
     */
    public function newInstance()
    {
        return new PartStreamFilterManager($this->streamDecoratorFactory);
    }
}
