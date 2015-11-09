<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\PartFactory;

/**
 * Description of ConsumerService
 *
 * @author Zaahid Bateson
 */
class ConsumerService
{
    protected $partFactory;
    
    public function __construct(PartFactory $partFactory)
    {
        $this->partFactory = $partFactory;
    }
    
    public function getAddressBaseConsumer()
    {
        return AddressBaseConsumer::getInstance($this, $this->partFactory);
    }
    
    public function getAddressConsumer()
    {
        return AddressConsumer::getInstance($this, $this->partFactory);
    }
    
    public function getAddressGroupConsumer()
    {
        return AddressGroupConsumer::getInstance($this, $this->partFactory);
    }
    
    public function getCommentConsumer()
    {
        return CommentConsumer::getInstance($this, $this->partFactory);
    }
    
    public function getGenericConsumer()
    {
        return GenericConsumer::getInstance($this, $this->partFactory);
    }
    
    public function getQuotedStringConsumer()
    {
        return QuotedStringConsumer::getInstance($this, $this->partFactory);
    }
    
    public function getDateConsumer()
    {
        return DateConsumer::getInstance($this, $this->partFactory);
    }
    
    public function getParameterConsumer()
    {
        return ParameterConsumer::getInstance($this, $this->partFactory);
    }
}
