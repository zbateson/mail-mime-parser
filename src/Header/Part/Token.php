<?php
namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MailMimeParser\Header\Part\Part;

/**
 * Holds a string value token that will require additional processing by a
 * consumer prior to returning to a client.
 * 
 * A Token is meant to hold a value for further processing -- for instance when
 * consuming an address list header (like From or To) -- before it's known what
 * type of Part it is (could be an email address, could be a name, or could be
 * a group.)
 *
 * @author Zaahid Bateson
 */
class Token extends Part
{
    /**
     * Initializes a token.
     * 
     * @param string $value the token's value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
