<?php
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Description of Address
 *
 * @author Zaahid Bateson
 */
class Address extends Parameter
{
    public function __construct($name, $email)
    {
        parent::__construct(
            $name,
            ''
        );
        // can't be mime-encoded
        $this->value = preg_replace('/\s+/', '', $email);
    }
    
    public function getEmail()
    {
        return $this->value;
    }
}
