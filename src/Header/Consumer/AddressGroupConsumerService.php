<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\AddressGroupPart;

/**
 * Parses a single group of addresses (as a named-group part of an address
 * header).
 *
 * Finds addresses using its AddressConsumerService sub-consumer separated by
 * commas, and ends processing once a semi-colon is found.
 *
 * Prior to returning to its calling client, AddressGroupConsumerService
 * constructs a single Part\AddressGroupPart object filling it with all located
 * addresses, and returns it.
 *
 * The AddressGroupConsumerService extends AddressBaseConsumerService to define
 * start/end tokens, token separators, and construct a Part\AddressGroupPart to
 * return.
 *
 * @author Zaahid Bateson
 */
class AddressGroupConsumerService extends AddressBaseConsumerService
{
    /**
     * Overridden to return patterns matching the beginning and end markers of a
     * group address: colon and semi-colon (":" and ";") characters.
     *
     * @return string[] the patterns
     */
    public function getTokenSeparators() : array
    {
        return [':', ';'];
    }

    /**
     * Returns true if the passed token is a semi-colon.
     */
    protected function isEndToken(string $token) : bool
    {
        return ($token === ';');
    }

    /**
     * Returns true if the passed token is a colon.
     */
    protected function isStartToken(string $token) : bool
    {
        return ($token === ':');
    }

    /**
     * Performs post-processing on parsed parts.
     *
     * Returns an array with a single
     * {@see AddressGroupPart} element with all email addresses from this and
     * any sub-groups.
     *
     * @param \ZBateson\MailMimeParser\Header\IHeaderPart[] $parts
     * @return AddressGroupPart[]|array
     */
    protected function processParts(array $parts) : array
    {
        $emails = [];
        foreach ($parts as $part) {
            if ($part instanceof AddressGroupPart) {
                $emails = \array_merge($emails, $part->getAddresses());
                continue;
            }
            $emails[] = $part;
        }
        $group = $this->partFactory->newAddressGroupPart($emails);
        return [$group];
    }
}
