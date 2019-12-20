<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of AddressPartTest
 *
 * @group HeaderParts
 * @group AddressPart
 * @covers ZBateson\MailMimeParser\Header\Part\AddressPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class AddressPartTest extends TestCase
{
    private $charsetConverter;

    public function setUp()
    {
        $this->charsetConverter = new MbWrapper();
    }

    public function testNameEmail()
    {
        $name = 'Julius Caeser';
        $email = 'gaius@altavista.com';
        $part = new AddressPart($this->charsetConverter, $name, $email);
        $this->assertEquals($name, $part->getName());
        $this->assertEquals($email, $part->getEmail());
    }

    public function testEmailSpacesStripped()
    {
        $email = "gaius julius\t\n caesar@altavista.com";
        $part = new AddressPart($this->charsetConverter, '', $email);
        $this->assertEquals('gaiusjuliuscaesar@altavista.com', $part->getEmail());
    }
}
