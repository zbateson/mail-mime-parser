<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Message\MessageParser;
use ZBateson\MailMimeParser\Message\Part\PartBuilderFactory;
use ZBateson\MailMimeParser\Message\Part\PartFactoryService;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;
use ZBateson\MailMimeParser\Stream\PartStream;
use ZBateson\MailMimeParser\Stream\ConvertStreamFilter;
use ZBateson\MailMimeParser\Stream\UUDecodeStreamFilter;
use ZBateson\MailMimeParser\Stream\CharsetStreamFilter;
use ZBateson\MailMimeParser\Stream\Base64DecodeStreamFilter;
use ZBateson\MailMimeParser\Util\CharsetConverter;
use ZBateson\MailMimeParser\Message\Part\PartStreamFilterManagerFactory;

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
     * @var \ZBateson\MailMimeParser\Stream\PartStreamRegistry singleton
     * 'service' instance
     */
    protected $partStreamRegistry;
    
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
            $this->getPartBuilderFactory(),
            $this->getPartStreamRegistry()
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
    
    /**
     * Constructs and returns a new CharsetConverter object.
     * 
     * @return \ZBateson\MailMimeParser\Util\CharsetConverter
     */
    public function newCharsetConverter()
    {
        return new CharsetConverter();
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
                $this->getPartStreamFilterManagerFactory()
            );
        }
        return $this->partFactoryService;
    }

    public function getPartBuilderFactory()
    {
        if ($this->partBuilderFactory === null) {
            $this->partBuilderFactory = new PartBuilderFactory(
                $this->getHeaderFactory(),
                PartStream::STREAM_WRAPPER_PROTOCOL
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
    
    public function getPartStreamFilterManagerFactory()
    {
        if ($this->partStreamFilterManagerFactory === null) {
            $this->partStreamFilterManagerFactory = new PartStreamFilterManagerFactory(
                ConvertStreamFilter::STREAM_DECODER_FILTER_NAME,
                Base64DecodeStreamFilter::STREAM_FILTER_NAME,
                UUDecodeStreamFilter::STREAM_FILTER_NAME,
                CharsetStreamFilter::STREAM_FILTER_NAME
            );
        }
        return $this->getInstance(
            'partStreamFilterManagerFactory',
            __NAMESPACE__ . '\Message\Part\PartStreamFilterManagerFactory'
        );
    }
    
    /**
     * Returns the part stream registry service instance.  The method also
     * registers the stream extension by calling registerStreamExtensions.
     * 
     * @return \ZBateson\MailMimeParser\Stream\PartStreamRegistry
     */
    public function getPartStreamRegistry()
    {
        if ($this->partStreamRegistry === null) {
            $this->registerStreamExtensions();
        }
        return $this->getInstance('partStreamRegistry', __NAMESPACE__ . '\Stream\PartStreamRegistry');
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
    
    /**
     * Registers stream extensions for PartStream and CharsetStreamFilter
     * 
     * @see stream_filter_register
     * @see stream_wrapper_register
     */
    protected function registerStreamExtensions()
    {
        stream_filter_register(
            UUDecodeStreamFilter::STREAM_FILTER_NAME, __NAMESPACE__ . '\Stream\UUDecodeStreamFilter');
        stream_filter_register(CharsetStreamFilter::STREAM_FILTER_NAME, __NAMESPACE__ . '\Stream\CharsetStreamFilter');
        stream_wrapper_register(PartStream::STREAM_WRAPPER_PROTOCOL, __NAMESPACE__ . '\Stream\PartStream');
        
        // originally created for HHVM compatibility, but decided to use them
        // instead of built-in stream filters for reliability -- it seems the
        // built-in base64-decode and encode stream filter does pretty much the
        // same thing as HHVM's -- it only works on smaller streams where the
        // entire stream comes in a single buffer.
        // In addition, in HHVM 3.15 there seems to be a problem registering
        // 'convert.quoted-printable-decode/encode -- so to make things simple
        // decided to use my version instead and name them mmp-convert.*
        // In 3.18-3.20, it seems we're not able to overwrite 'convert.*'
        // filters, so now they're all named mmp-convert.*
        stream_filter_register(
            ConvertStreamFilter::STREAM_DECODER_FILTER_NAME,
            __NAMESPACE__ . '\Stream\ConvertStreamFilter'
        );
        stream_filter_register(
            Base64DecodeStreamFilter::STREAM_FILTER_NAME,
            __NAMESPACE__ . '\Stream\Base64DecodeStreamFilter'
        );
    }
}
