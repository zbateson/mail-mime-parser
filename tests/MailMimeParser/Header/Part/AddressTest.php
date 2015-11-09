<?php

use ZBateson\MailMimeParser\Header\Part\Address;

/**
 * Description of AddressTest
 *
 * @group HeaderParts
 * @group Address
 * @author Zaahid Bateson
 */
class AddressTest extends PHPUnit_Framework_TestCase
{
    public function testNameEmail()
    {
        $name = 'Julius Caeser';
        $email = 'gaius@altavista.com';
        $part = new Address($name, $email);
        $this->assertEquals($name, $part->getName());
        $this->assertEquals($email, $part->getEmail());
    }
    
    public function testEmailSpacesStripped()
    {
        $email = "gaius julius\t\n caesar@altavista.com";
        $part = new Address('', $email);
        $this->assertEquals('gaiusjuliuscaesar@altavista.com', $part->getEmail());
    }
}
