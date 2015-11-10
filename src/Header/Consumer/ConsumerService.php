<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\PartFactory;

/**
 * Simple service provider for consumer singletons.
 *
 * @author Zaahid Bateson
 */
class ConsumerService
{
    /**
     * @var \ZBateson\MailMimeParser\Header\Part\PartFactory 
     */
    protected $partFactory;
    
    /**
     * Sets up the PartFactory member variable.
     * 
     * @param PartFactory $partFactory
     */
    public function __construct(PartFactory $partFactory)
    {
        $this->partFactory = $partFactory;
    }
    
    /**
     * Returns the AddressBaseConsumer singleton instance.
     * 
     * @return \ZBateson\MailMimeParser\Header\Consumer\AddressBaseConsumer
     */
    public function getAddressBaseConsumer()
    {
        return AddressBaseConsumer::getInstance($this, $this->partFactory);
    }
    
    /**
     * Returns the AddressConsumer singleton instance.
     * 
     * @return \ZBateson\MailMimeParser\Header\Consumer\AddressConsumer
     */
    public function getAddressConsumer()
    {
        return AddressConsumer::getInstance($this, $this->partFactory);
    }
    
    /**
     * Returns the AddressGroupConsumer singleton instance.
     * 
     * @return \ZBateson\MailMimeParser\Header\Consumer\AddressGroupConsumer
     */
    public function getAddressGroupConsumer()
    {
        return AddressGroupConsumer::getInstance($this, $this->partFactory);
    }
    
    /**
     * Returns the CommentConsumer singleton instance.
     * 
     * @return \ZBateson\MailMimeParser\Header\Consumer\CommentConsumer
     */
    public function getCommentConsumer()
    {
        return CommentConsumer::getInstance($this, $this->partFactory);
    }
    
    /**
     * Returns the GenericConsumer singleton instance.
     * 
     * @return \ZBateson\MailMimeParser\Header\Consumer\GenericConsumer
     */
    public function getGenericConsumer()
    {
        return GenericConsumer::getInstance($this, $this->partFactory);
    }
    
    /**
     * Returns the QuotedStringConsumer singleton instance.
     * 
     * @return \ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer
     */
    public function getQuotedStringConsumer()
    {
        return QuotedStringConsumer::getInstance($this, $this->partFactory);
    }
    
    /**
     * Returns the DateConsumer singleton instance.
     * 
     * @return \ZBateson\MailMimeParser\Header\Consumer\DateConsumer
     */
    public function getDateConsumer()
    {
        return DateConsumer::getInstance($this, $this->partFactory);
    }
    
    /**
     * Returns the ParameterConsumer singleton instance.
     * 
     * @return \ZBateson\MailMimeParser\Header\Consumer\ParameterConsumer
     */
    public function getParameterConsumer()
    {
        return ParameterConsumer::getInstance($this, $this->partFactory);
    }
}
