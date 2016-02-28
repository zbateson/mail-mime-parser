<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Constructs and returns HeaderPart objects.
 *
 * @author Zaahid Bateson
 */
class HeaderPartFactory
{
    /**
     * Creates and returns a default HeaderPart for this factory, allowing
     * subclass factories for specialized HeaderParts.
     * 
     * The default implementation returns a new Token.
     * 
     * @param string $value
     * @return HeaderPart
     */
    public function newInstance($value)
    {
        return $this->newToken($value);
    }
    
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
     * Initializes and returns a new LiteralPart.
     * 
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\LiteralPart
     */
    public function newLiteralPart($value)
    {
        return new LiteralPart($value);
    }
    
    /**
     * Initializes and returns a new MimeLiteralPart.
     * 
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\MimeLiteralPart
     */
    public function newMimeLiteralPart($value)
    {
        return new MimeLiteralPart($value);
    }
    
    /**
     * Initializes and returns a new CommentPart.
     * 
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\CommentPart
     */
    public function newCommentPart($value)
    {
        return new CommentPart($value);
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
     * Initializes and returns a new DatePart
     * 
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\DatePart
     */
    public function newDatePart($value)
    {
        return new DatePart($value);
    }
    
    /**
     * Initializes and returns a new ParameterPart.
     * 
     * @param string $name
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\Part\ParameterPart
     */
    public function newParameterPart($name, $value)
    {
        return new ParameterPart($name, $value);
    }
}
