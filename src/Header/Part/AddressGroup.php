<?php
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Description of AddressGroup
 *
 * @author Zaahid Bateson
 */
class AddressGroup extends MimeLiteral
{
    protected $addresses;
    
    public function __construct(array $addresses, $name = '')
    {
        parent::__construct($name);
        $this->addresses = $addresses;
    }
    
    public function getAddresses()
    {
        return $this->addresses;
    }
    
    public function getAddress($index)
    {
        return $this->addresses[$index];
    }
    
    public function getName()
    {
        return $this->value;
    }
}
