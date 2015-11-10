<?php
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Holds a single address or name/address pair.
 * 
 * The name part of the address may be mime-encoded, but the email address part
 * can't be mime-encoded.  Any whitespace in the email address part is stripped
 * out.
 *
 * A convenience method, getEmail, is provided for clarity -- but getValue
 * returns the email address as well.
 * 
 * @author Zaahid Bateson
 */
class Address extends Parameter
{
    /**
     * Constructs an Address part.
     * 
     * The passed $name may be mime-encoded.  $email is stripped of any
     * whitespace.
     * 
     * @param string $name
     * @param string $email
     */
    public function __construct($name, $email)
    {
        parent::__construct(
            $name,
            ''
        );
        // can't be mime-encoded
        $this->value = preg_replace('/\s+/', '', $email);
    }
    
    /**
     * Returns the email address.
     * 
     * @return string
     */
    public function getEmail()
    {
        return $this->value;
    }
}
