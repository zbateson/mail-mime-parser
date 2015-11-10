<?php
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Holds a group of addresses, and an optional group name.
 * 
 * Because AddressGroupConsumer is only called once a colon (":") character is
 * found, an AddressGroup is initially constructed without a $name.  Once it is
 * returned to AddressConsumer, a new AddressGroup is created out of
 * AddressGroupConsumer's AddressGroup.
 *
 * @author Zaahid Bateson
 */
class AddressGroup extends MimeLiteral
{
    /**
     * @var Address[] an array of Address parts 
     */
    protected $addresses;
    
    /**
     * Creates an AddressGroup out of the passed array of Address parts and an
     * optional name (which may be mime-encoded).
     * 
     * @param Address[] $addresses
     * @param string $name
     */
    public function __construct(array $addresses, $name = '')
    {
        parent::__construct($name);
        $this->addresses = $addresses;
    }
    
    /**
     * Return the AddressGroup's array of addresses.
     * 
     * @return Address[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }
    
    /**
     * Returns the Address at the passed index or null.
     * 
     * @param int $index
     * @return Address
     */
    public function getAddress($index)
    {
        if (!isset($this->addresses[$index])) {
            return null;
        }
        return $this->addresses[$index];
    }
    
    /**
     * Returns the name of the group
     * 
     * @return string
     */
    public function getName()
    {
        return $this->value;
    }
}
