<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Factory;

use ReflectionClass;
use ZBateson\MailMimeParser\Message\MessageService;
use ZBateson\MailMimeParser\Message\Factory\PartFilterFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Abstract factory for subclasses of MessagePart.
 *
 * @author Zaahid Bateson
 */
abstract class MessagePartFactory
{
    /**
     * @var StreamFactory
     */
    protected $streamFactory;

    /**
     * @var PartStreamContainerFactory
     */
    protected $partStreamContainerFactory;

    public function __construct(StreamFactory $streamFactory, PartStreamContainerFactory $partStreamContainerFactory) {
        $this->streamFactory = $streamFactory;
        $this->partStreamContainerFactory = $partStreamContainerFactory;
    }

    /**
     * Constructs a new MessagePart object and returns it
     * 
     * @return \ZBateson\MailMimeParser\Message\IMessagePart
     */
    public abstract function newInstance();
}
