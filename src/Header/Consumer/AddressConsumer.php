<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\Token;
use ZBateson\MailMimeParser\Header\Part\AddressGroup;

/**
 * Parses a single part of an address header.
 * 
 * Represents a single part of a list of addresses.  A part could be one email
 * address, or one 'group' containing multiple addresses.  The consumer ends on
 * finding either a comma token, representing a separation between addresses, or
 * a semi-colon token representing the end of a group.
 * 
 * A single email address may consist of just an email, or a name and an email
 * address.  Both of these are valid examples of a From header:
 *  - From: jonsnow@winterfell.com
 *  - From: Jon Snow <jonsnow@winterfell.com>
 * 
 * Groups must be named, for example:
 *  - To: Winterfell: jonsnow@winterfell.com, Arya Stark <arya@winterfell.com>;
 *
 * Addresses may contain quoted parts and comments, and names may be mime-header
 * encoded (need to review RFC to be sure of this as its been a while).
 * 
 * @author Zaahid Bateson
 */
class AddressConsumer extends AbstractConsumer
{
    /**
     * Returns the following as sub-consumers:
     *  - \ZBateson\MailMimeParser\Header\Consumer\AddressGroupConsumer
     *  - \ZBateson\MailMimeParser\Header\Consumer\CommentConsumer
     *  - \ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer
     * 
     * @return AbstractConsumer[] the sub-consumers
     */
    protected function getSubConsumers()
    {
        return [
            $this->consumerService->getAddressGroupConsumer(),
            $this->consumerService->getCommentConsumer(),
            $this->consumerService->getQuotedStringConsumer(),
        ];
    }
    
    /**
     * Overridden to return patterns matching the beginning part of an address
     * in a name/address part ("<" and ">" chars), end tokens ("," and ";"), and
     * whitespace.
     * 
     * @return string[] the patterns
     */
    public function getTokenSeparators()
    {
        return ['<', '>', ',', ';', '\s+'];
    }
    
    /**
     * Returns true for commas and semi-colons.
     * 
     * Although the semi-colon is not strictly the end token of an
     * AddressConsumer, it could end a parent AddressGroupConsumer. I can't
     * think of a valid scenario where this would be an issue, but additional
     * thought may be needed (and documented here).
     * 
     * @param string $token
     * @return boolean false
     */
    protected function isEndToken($token)
    {
        return ($token === ',' || $token === ';');
    }
    
    /**
     * AddressConsumer is "greedy", so this always returns true.
     * 
     * @param string $token
     * @return boolean false
     */
    protected function isStartToken($token)
    {
        return true;
    }
    
    /**
     * Creates and returns a \ZBateson\MailMimeParser\Header\Part\Token out of
     * the passed string token and returns it, unless the token is an escaped
     * literal, in which case a Literal is returned to avoid it being processed
     * as a separator in processParts.
     * 
     * @param string $token
     * @param bool $isLiteral
     * @return \ZBateson\MailMimeParser\Header\Part\Part
     */
    protected function getPartForToken($token, $isLiteral)
    {
        if ($isLiteral) {
            return $this->partFactory->newLiteral($token);
        } elseif (preg_match('/^\s+$/', $token)) {
            return $this->partFactory->newToken(' ');
        }
        return $this->partFactory->newToken($token);
    }
    
    /**
     * Performs final processing on parsed parts.
     * 
     * AddressConsumer's implementation looks for tokens representing the
     * beginning of an address part, to create a Part\AddressPart out of a
     * name/address pair, or assign the name part to a parsed Part\AddressGroup
     * returned from its AddressGroupConsumer sub-consumer.
     * 
     * The returned array consists of a single element - either a
     * Part\AddressPart or a Part\AddressGroup.
     * 
     * @param ZBateson\MailMimeParser\Header\Part\Part[] $parts
     * @return ZBateson\MailMimeParser\Header\Part\Part[]
     */
    protected function processParts(array $parts)
    {
        $strName = '';
        $strValue = '';
        foreach ($parts as $part) {
            $p = $part->getValue();
            if ($part instanceof AddressGroup) {
                return [
                    $this->partFactory->newAddressGroup(
                        $part->getAddresses(),
                        $strValue
                    )
                ];
            } elseif ($part instanceof Token) {
                if ($p === '<') {
                    $strName = $strValue;
                    $strValue = '';
                    continue;
                } elseif ($p === '>') {
                    continue;
                }
            }
            $strValue .= $part->getValue();
        }
        return [$this->partFactory->newAddressPart($strName, $strValue)];
    }
}
