<?php
namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Part\Address;
use ZBateson\MailMimeParser\Header\Part\AddressGroup;

/**
 * Description of Header
 *
 * @author Zaahid Bateson
 */
class AddressHeader extends StructuredHeader
{
    protected $addresses;
    protected $groups;
    protected $partGlue = ',';
    
    protected function setupConsumer()
    {
        $this->consumer = $this->consumerService->getAddressConsumer();
        $this->consumers = [
            $this->consumerService->getQuotedStringConsumer(),
            $this->consumerService->getCommentConsumer(),
            $this->consumerService->getAddressEmailConsumer(),
            $this->consumerService->getAddressConsumer(),
        ];
    }
    
    protected function parseValue()
    {
        parent::parseValue();
        if (empty($this->parts)) {
            return;
        }
        
        $this->addresses = [];
        $this->groups = [];
        foreach ($this->parts as $part) {
            if ($part instanceof Address) {
                $this->addresses[] = $part;
            } elseif ($part instanceof AddressGroup) {
                $this->addresses = array_merge(
                    $this->addresses,
                    $part->getAddresses()
                );
                $this->groups[] = $part;
            }
        }
    }
    
    public function getIteratory()
    {
        return new \ArrayIterator($this->addresses);
    }
}
