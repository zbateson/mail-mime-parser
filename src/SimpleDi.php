<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Message\MessageParser;
use ZBateson\MailMimeParser\Message\Part\Factory\PartBuilderFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\PartFactoryService;
use ZBateson\MailMimeParser\Message\Part\Factory\PartStreamFilterManagerFactory;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;
use ZBateson\StreamDecorators\Util\CharsetConverter;

/**
 * Dependency injection container for use by ZBateson\MailMimeParser - because a
 * more complex one seems like overkill.
 * 
 * Constructs objects and whatever dependencies they require.
 *
 * @author Zaahid Bateson
 */
class SimpleDi
{
    /**
     * @var type 
     */
    protected $partBuilderFactory;
    
    /**
     * @var type 
     */
    protected $partFactoryService;
    
    /**
     * @var type 
     */
    protected $partFilterFactory;
    
    /**
     * @var type 
     */
    protected $partStreamFilterManagerFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory singleton 'service'
     * instance
     */
    protected $headerFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Header\Part\HeaderPartFactory singleton
     * 'service' instance
     */
    protected $headerPartFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory
     * singleton 'service' instance
     */
    protected $mimeLiteralPartFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Header\Consumer\ConsumerService singleton
     * 'service' instance
     */
    protected $consumerService;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\Writer\MessageWriterService 
     * singleton 'service' instance for getting MimePartWriter and MessageWriter
     * instances
     */
    protected $messageWriterService;

    protected $streamFactory;
    
    /**
     * Constructs a SimpleDi - call singleton() to invoke
     */
    private function __construct()
    {
    }
    
    /**
     * Returns the singleton instance.
     * 
     * @return \ZBateson\MailMimeParser\SimpleDi
     */
    public static function singleton()
    {
        static $singleton = null;
        if ($singleton === null) {
            $singleton = new SimpleDi();
        }
        return $singleton;
    }
    
    /**
     * Returns a singleton 'service' instance for the given service named $var
     * with a class type of $class.
     * 
     * @param string $var the name of the service
     * @param string $class the name of the class
     * @return mixed the service object
     */
    protected function getInstance($var, $class)
    {
        if ($this->$var === null) {
            $this->$var = new $class();
        }
        return $this->$var;
    }
    
    /**
     * Constructs and returns a new MessageParser object.
     * 
     * @return \ZBateson\MailMimeParser\Message\MessageParser
     */
    public function newMessageParser()
    {
        return new MessageParser(
            $this->getPartFactoryService(),
            $this->getPartBuilderFactory()
        );
    }
    
    /**
     * Returns a MessageWriterService instance.
     * 
     * @return MessageWriterService
     */
    public function getMessageWriterService()
    {
        if ($this->messageWriterService === null) {
            $this->messageWriterService = new MessageWriterService();
        }
        return $this->messageWriterService;
    }
    
    public function getPartFilterFactory()
    {
        return $this->getInstance(
            'partFilterFactory',
            __NAMESPACE__ . '\Message\PartFilterFactory'
        );
    }
    
    /**
     * 
     * @return type
     */
    public function getPartFactoryService()
    {
        if ($this->partFactoryService === null) {
            $this->partFactoryService = new PartFactoryService(
                $this->getHeaderFactory(),
                $this->getPartFilterFactory(),
                $this->getStreamFactory(),
                $this->getPartStreamFilterManagerFactory()
            );
        }
        return $this->partFactoryService;
    }

    public function getPartBuilderFactory()
    {
        if ($this->partBuilderFactory === null) {
            $this->partBuilderFactory = new PartBuilderFactory(
                $this->getHeaderFactory()
            );
        }
        return $this->partBuilderFactory;
    }
    
    /**
     * Returns the header factory service instance.
     * 
     * @return \ZBateson\MailMimeParser\Header\HeaderFactory
     */
    public function getHeaderFactory()
    {
        if ($this->headerFactory === null) {
            $this->headerFactory = new HeaderFactory($this->getConsumerService());
        }
        return $this->headerFactory;
    }

    public function getStreamFactory()
    {
        return $this->getInstance(
            'streamFactory',
            __NAMESPACE__ . '\Stream\StreamFactory'
        );
    }
    
    public function getPartStreamFilterManagerFactory()
    {
        if ($this->partStreamFilterManagerFactory === null) {
            $this->partStreamFilterManagerFactory = new PartStreamFilterManagerFactory(
                $this->getStreamFactory()
            );
        }
        return $this->getInstance(
            'partStreamFilterManagerFactory',
            __NAMESPACE__ . '\Message\Part\PartStreamFilterManagerFactory'
        );
    }
    
    public function getCharsetConverter()
    {
        return new CharsetConverter();
    }
    
    /**
     * Returns the part factory service
     * 
     * @return \ZBateson\MailMimeParser\Header\Part\HeaderPartFactory
     */
    public function getHeaderPartFactory()
    {
        if ($this->headerPartFactory === null) {
            $this->headerPartFactory = new HeaderPartFactory($this->getCharsetConverter());
        }
        return $this->headerPartFactory;
    }
    
    /**
     * Returns the MimeLiteralPartFactory service
     * 
     * @return \ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory
     */
    public function getMimeLiteralPartFactory()
    {
        if ($this->mimeLiteralPartFactory === null) {
            $this->mimeLiteralPartFactory = new MimeLiteralPartFactory($this->getCharsetConverter());
        }
        return $this->mimeLiteralPartFactory;
    }
    
    /**
     * Returns the header consumer service
     * 
     * @return \ZBateson\MailMimeParser\Header\Consumer\ConsumerService
     */
    public function getConsumerService()
    {
        if ($this->consumerService === null) {
            $this->consumerService = new ConsumerService(
                $this->getHeaderPartFactory(),
                $this->getMimeLiteralPartFactory()
            );
        }
        return $this->consumerService;
    }
    
}
