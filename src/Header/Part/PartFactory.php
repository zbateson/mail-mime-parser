<?php
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Description of PartFactory
 *
 * @author Zaahid Bateson
 */
class PartFactory
{
    public function newToken($value)
    {
        return new Token($value);
    }
    
    public function newLiteral($token)
    {
        return new Literal($token);
    }
    
    public function newMimeLiteral($token)
    {
        return new MimeLiteral($token);
    }
    
    public function newComment($token)
    {
        return new Comment($token);
    }
    
    public function newAddress($name, $email)
    {
        return new Address($name, $email);
    }
    
    public function newAddressGroup(array $addresses, $name = '')
    {
        return new AddressGroup($addresses, $name);
    }
    
    public function newDate($token)
    {
        return new Date($token);
    }
    
    public function newParameter($name, $value)
    {
        return new Parameter($name, $value);
    }
}
