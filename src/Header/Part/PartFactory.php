<?php
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Constructs and returns Part objects.
 *
 * @author Zaahid Bateson
 */
class PartFactory
{
    /**
     * Initializes and returns a new Token.
     * 
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\Token
     */
    public function newToken($value)
    {
        return new Token($value);
    }
    
    /**
     * Initializes and returns a new Literal.
     * 
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\Literal
     */
    public function newLiteral($value)
    {
        return new Literal($value);
    }
    
    /**
     * Initializes and returns a new MimeLiteral.
     * 
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\MimeLiteral
     */
    public function newMimeLiteral($value)
    {
        return new MimeLiteral($value);
    }
    
    /**
     * Initializes and returns a new Comment.
     * 
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\Comment
     */
    public function newComment($value)
    {
        return new Comment($value);
    }
    
    /**
     * Initializes and returns a new AddressPart.
     * 
     * @param string $name
     * @param string $email
     * @return \ZBateson\MailMimeParser\Header\Part\AddressPart
     */
    public function newAddressPart($name, $email)
    {
        return new AddressPart($name, $email);
    }
    
    /**
     * Initializes and returns a new AddressGroupPart
     * 
     * @param array $addresses
     * @param string $name
     * @return \ZBateson\MailMimeParser\Header\Part\AddressGroupPart
     */
    public function newAddressGroupPart(array $addresses, $name = '')
    {
        return new AddressGroupPart($addresses, $name);
    }
    
    /**
     * Initializes and returns a new Date
     * 
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\Date
     */
    public function newDate($value)
    {
        return new Date($value);
    }
    
    /**
     * Initializes and returns a new Parameter.
     * 
     * @param string $name
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\Parameter
     */
    public function newParameter($name, $value)
    {
        return new Parameter($name, $value);
    }
}
