<?php

use ZBateson\MailMimeParser\Header\Part\AddressPart;

/**
 * Description of AddressPartTest
 *
 * @group HeaderParts
 * @group Address
 * @author Zaahid Bateson
 */
class AddressPartTest extends PHPUnit_Framework_TestCase
{
    public function testNameEmail()
    {
        $name = 'Julius Caeser';
        $email = 'gaius@altavista.com';
        $part = new AddressPart($name, $email);
        $this->assertEquals($name, $part->getName());
        $this->assertEquals($email, $part->getEmail());
    }
    
    public function testEmailSpacesStripped()
    {
        $email = "gaius julius\t\n caesar@altavista.com";
        $part = new AddressPart('', $email);
        $this->assertEquals('gaiusjuliuscaesar@altavista.com', $part->getEmail());
    }
}
