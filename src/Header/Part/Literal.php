<?php
namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MailMimeParser\Header\Part\Part;

/**
 * A literal header string part.  The value of the part is not transformed or
 * changed in any way.
 *
 * @author Zaahid Bateson
 */
class Literal extends Part
{
    /**
     * Constructs a Literal out of the passed Part.
     * 
     * @param \ZBateson\MailMimeParser\Header\Part\Part $part
     */
    public function __construct($token)
    {
        $this->value = $token;
    }
}
