<?php
namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\PartFactory;
use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * Description of Header
 *
 * @author Zaahid Bateson
 */
class Header
{
    protected $consumerService;
    protected $partFactory;
    protected $name;
    protected $part;
    protected $value;
    protected $rawValue;
    protected $consumer;
    
    public function __construct(ConsumerService $consumerService, PartFactory $partFactory, $name, $value)
    {
        $this->consumerService = $consumerService;
        $this->partFactory = $partFactory;
        $this->name = $name;
        $this->rawValue = $value;
        
        $this->setupConsumer();
        $this->parseValue();
    }
    
    protected function setupConsumer()
    {
        $this->consumer = $this->consumerService->getGenericConsumer();
    }

    protected function parseValue()
    {
        if (!empty($this->rawValue)) {
            $this->part = $this->consumer->__invoke(
                $this->consumer->newPart(),
                $this->partFactory->newToken($this->rawValue)
            );
            $this->value = $this->part->value;
        }
    }

    public function __get($var)
    {
        return $this->$var;
    }
    
    public function __isset($var)
    {
        return isset($this->$var);
    }
    
    public function __toString()
    {
        return "{$this->name}: {$this->rawValue}";
    }
}
