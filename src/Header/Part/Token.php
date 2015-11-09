<?php
namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MailMimeParser\Header\Part\Part;

/**
 * Represents a single consumed token from a Header\Consumer that will be added
 * to a Part.
 * 
 * The class checks the encoding on the passed $value to the constructor and
 * attempts to convert it to UTF-8.
 *
 * @author Zaahid Bateson
 */
class Token extends Part
{
    /**
     * Initializes a token, converting it to UTF-8.
     * 
     * @param string $value the token's value
     */
    public function __construct($value)
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        }
        $this->value = $value;
    }
}
