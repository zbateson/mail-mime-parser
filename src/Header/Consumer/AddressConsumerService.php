<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\AddressGroupPart;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MailMimeParser\Header\Part\CommentPart;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\LiteralPart;

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
 * encoded.
 *
 * @author Zaahid Bateson
 */
class AddressConsumerService extends AbstractConsumerService
{
    public function __construct(
        HeaderPartFactory $partFactory,
        AddressGroupConsumerService $addressGroupConsumerService,
        AddressEmailConsumerService $addressEmailConsumerService,
        CommentConsumerService $commentConsumerService,
        QuotedStringConsumerService $quotedStringConsumerService
    ) {
        $addressGroupConsumerService->setAddressConsumerService($this);
        parent::__construct(
            $partFactory,
            [
                $addressGroupConsumerService,
                $addressEmailConsumerService,
                $commentConsumerService,
                $quotedStringConsumerService
            ]
        );
    }

    /**
     * Overridden to return patterns matching end tokens ("," and ";"), and
     * whitespace.
     *
     * @return string[] the patterns
     */
    public function getTokenSeparators() : array
    {
        return [',', ';', '\s+'];
    }

    /**
     * Returns true for commas and semi-colons.
     *
     * Although the semi-colon is not strictly the end token of an
     * AddressConsumerService, it could end a parent
     * {@see AddressGroupConsumerService}.
     */
    protected function isEndToken(string $token) : bool
    {
        return ($token === ',' || $token === ';');
    }

    /**
     * AddressConsumer is "greedy", so this always returns true.
     */
    protected function isStartToken(string $token) : bool
    {
        return true;
    }

    /**
     * Performs final processing on parsed parts.
     *
     * AddressConsumerService's implementation looks for tokens representing the
     * beginning of an address part, to create a {@see AddressPart} out of a
     * name/address pair, or assign the name part to a parsed
     * {@see AddressGroupPart} returned from its AddressGroupConsumerService
     * sub-consumer.
     *
     * The returned array consists of a single element - either an
     * {@see AddressPart} or an {@see AddressGroupPart}.
     *
     * @param \ZBateson\MailMimeParser\Header\IHeaderPart[] $parts
     * @return \ZBateson\MailMimeParser\Header\IHeaderPart[]|array
     */
    protected function processParts(array $parts) : array
    {
        $strName = '';
        $strEmail = '';
        foreach ($parts as $part) {
            if ($part instanceof AddressGroupPart) {
                return [
                    $this->partFactory->newAddressGroupPart(
                        $part->getAddresses(),
                        $strName
                    )
                ];
            } elseif ($part instanceof AddressPart) {
                return [$this->partFactory->newAddressPart($strName, $part->getEmail())];
            } elseif ((($part instanceof LiteralPart) && !($part instanceof CommentPart)) && $part->getValue() !== '') {
                $strEmail .= '"' . \preg_replace('/(["\\\])/', '\\\$1', $part->getValue()) . '"';
            } else {
                $strEmail .= \preg_replace('/\s+/', '', $part->getValue());
            }
            $strName .= $part->getValue();
        }
        return [$this->partFactory->newAddressPart('', $strEmail)];
    }
}
