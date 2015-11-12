<?php
namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;

/**
 * Abstract base class representing a mime email's header.
 * 
 * The base class sets up the header's consumer, sets the name of the header and
 * calls the consumer to parse the header's value.
 * 
 * AbstractHeader::getConsumer is an abstract method that must be overridden to
 * return an appropriate Consumer\AbstractConsumer type.
 * 
 * AbstractHeader::parseHeaderValue shou
 *
 * @author Zaahid Bateson
 */
abstract class AbstractHeader
{
    /**
     * @var string the name of the header
     */
    protected $name;
    
    /**
     * @var \ZBateson\MailMimeParser\Header\Consumer\Part\Part the header's
     * part value (as returned from the consumer)
     */
    protected $part;
    
    /**
     * @var string the raw value
     */
    protected $rawValue;
    
    /**
     * Assigns the header's name and raw value, then calls getConsumer and
     * parseHeaderValue to extract a parsed value.
     * 
     * @param ConsumerService $consumerService
     * @param string $name
     * @param string $value
     */
    public function __construct(ConsumerService $consumerService, $name, $value)
    {
        $this->name = $name;
        $this->rawValue = $value;
        
        $consumer = $this->getConsumer($consumerService);
        $this->setParseHeaderValue($consumer);
    }
    
    /**
     * Returns the header's Consumer
     * 
     * @return \ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
     */
    abstract protected function getConsumer(ConsumerService $consumerService);
    
    /**
     * Calls the consumer and assigns the parsed parts to member variables.
     * 
     * The default implementation assigns the returned value to $this->part.
     * 
     * @param AbstractConsumer $consumer
     */
    protected function setParseHeaderValue(AbstractConsumer $consumer)
    {
        $this->part = $consumer($this->rawValue);
    }

    /**
     * Returns the Part object associated with this header.
     * 
     * @return \ZBateson\MailMimeParser\Header\Part\Part
     */
    public function getPart()
    {
        return $this->part;
    }
    
    /**
     * Returns the parsed value of the header -- calls getValue on $this->part
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->part->getValue();
    }
    
    /**
     * Returns the raw value of the header prior to any processing.
     * 
     * @return string
     */
    public function getRawValue()
    {
        return $this->rawValue;
    }
    
    /**
     * Returns the name of the header.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->getName();
    }
    
    /**
     * Returns the string representation of the header.  At the moment this is
     * just in the form of:
     * 
     * <HeaderName>: <RawValue>
     * 
     * No additional processing is performed (for instance to wrap long lines.)
     * 
     * @return string
     */
    public function __toString()
    {
        return "{$this->name}: {$this->rawValue}";
    }
}
