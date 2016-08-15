<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Stream\PartStream;
use ZBateson\MailMimeParser\Stream\UUDecodeStreamFilter;
use ZBateson\MailMimeParser\Stream\UUEncodeStreamFilter;
use ZBateson\MailMimeParser\Stream\CharsetStreamFilter;
use ZBateson\MailMimeParser\Stream\QuotedPrintableDecodeStreamFilter;
use ZBateson\MailMimeParser\Stream\Base64DecodeStreamFilter;
use ZBateson\MailMimeParser\Stream\Helper\CharsetConverter;

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
     * @var \ZBateson\MailMimeParser\MimePartFactory singleton 'service' instance
     */
    protected $partFactory;
    
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
        if (empty($singleton)) {
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
     * @return \ZBateson\MailMimeParser\MessageParser
     */
    public function newMessageParser()
    {
        return new MessageParser(
            $this->newMessage(),
            $this->getPartFactory(),
            $this->getPartStreamRegistry()
        );
    }
    
    /**
     * Constructs and returns a new Message object.
     * 
     * @return \ZBateson\MailMimeParser\Message
     */
    public function newMessage()
    {
        return new Message(
            $this->getHeaderFactory()
        );
    }
    
    /**
     * Constructs and returns a new CharsetConverter object.
     * 
     * @param string $fromCharset source charset
     * @param string $toCharset destination charset
     * @return \ZBateson\MailMimeParser\Stream\Helper\CharsetConverter
     */
    public function newCharsetConverter($fromCharset, $toCharset)
    {
        return new CharsetConverter(
            $fromCharset,
            $toCharset
        );
    }
    
    /**
     * Returns the part factory service instance.
     * 
     * @return \ZBateson\MailMimeParser\MimePartFactory
     */
    public function getPartFactory()
    {
        if ($this->partFactory === null) {
            $this->partFactory = new MimePartFactory(
                $this->getHeaderFactory()
            );
        }
        return $this->partFactory;
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
    
    /**
     * Returns the part factory service
     * 
     * @return \ZBateson\MailMimeParser\Header\Part\HeaderPartFactory
     */
    public function getHeaderPartFactory()
    {
        return $this->getInstance('headerPartFactory', __NAMESPACE__ . '\Header\Part\HeaderPartFactory');
    }
    
    /**
     * Returns the MimeLiteralPartFactory service
     * 
     * @return \ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory
     */
    public function getMimeLiteralPartFactory()
    {
        return $this->getInstance('mimeLiteralPartFactory', __NAMESPACE__ . '\Header\Part\MimeLiteralPartFactory');
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
        stream_filter_register(UUDecodeStreamFilter::STREAM_FILTER_NAME, __NAMESPACE__ . '\Stream\UUDecodeStreamFilter');
        stream_filter_register(UUEncodeStreamFilter::STREAM_FILTER_NAME, __NAMESPACE__ . '\Stream\UUEncodeStreamFilter');
        stream_filter_register(CharsetStreamFilter::STREAM_FILTER_NAME, __NAMESPACE__ . '\Stream\CharsetStreamFilter');
        stream_wrapper_register(PartStream::STREAM_WRAPPER_PROTOCOL, __NAMESPACE__ . '\Stream\PartStream');
        
        // originally created for HHVM compatibility, but decided to use them
        // instead of built-in stream filters for reliability -- it seems the
        // built-in base64-decode stream filter errors out when seeking back...
        // possibly because it doesn't ignore whitespace completely?  Using
        // line-break-chars didn't seem to help.
        stream_filter_register(
            QuotedPrintableDecodeStreamFilter::STREAM_FILTER_NAME,
            __NAMESPACE__ . '\Stream\QuotedPrintableDecodeStreamFilter'
        );
        stream_filter_register(
            Base64DecodeStreamFilter::STREAM_FILTER_NAME,
            __NAMESPACE__ . '\Stream\Base64DecodeStreamFilter'
        );
    }
}
