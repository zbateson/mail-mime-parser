<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

use Psr\Log\LogLevel;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Holds a group of addresses, and an optional group name.
 *
 * Because AddressGroupConsumer is only called once a colon (":") character is
 * found, an AddressGroupPart is initially constructed without a $name.  Once it
 * is returned to AddressConsumer, a new AddressGroupPart is created out of
 * AddressGroupConsumer's AddressGroupPart.
 *
 * @author Zaahid Bateson
 */
class AddressGroupPart extends MimeLiteralPart
{
    /**
     * @var AddressPart[] an array of AddressParts
     */
    protected array $addresses;

    /**
     * Creates an AddressGroupPart out of the passed array of AddressParts and an
     * optional name (which may be mime-encoded).
     *
     * @param AddressPart[] $addresses
     */
    public function __construct(MbWrapper $charsetConverter, array $addresses, string $name = '')
    {
        parent::__construct($charsetConverter, \trim($name));
        $this->addresses = $addresses;
    }

    /**
     * Return the AddressGroupPart's array of addresses.
     *
     * @return AddressPart[] An array of address parts.
     */
    public function getAddresses() : array
    {
        return $this->addresses;
    }

    /**
     * Returns the AddressPart at the passed index or null.
     *
     * @param int $index The 0-based index.
     * @return ?AddressPart The address.
     */
    public function getAddress(int $index) : ?AddressPart
    {
        if (!isset($this->addresses[$index])) {
            return null;
        }
        return $this->addresses[$index];
    }

    /**
     * Returns the name of the group
     *
     * @return string The name
     */
    public function getName() : string
    {
        return $this->value;
    }

    protected function getErrorBagChildren() : array
    {
        return $this->addresses;
    }

    protected function validate() : void
    {
        if ($this->value === null || \mb_strlen($this->value) === 0) {
            $this->addError('Address group doesn\'t have a name', LogLevel::ERROR);
        }
        if (empty($this->addresses)) {
            $this->addError('Address group doesn\'t have any email addresses defined in it', LogLevel::NOTICE);
        }
    }
}
