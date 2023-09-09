<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;

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
    // @phpstan-ignore-next-line
    private $charsetConverter;

    protected function setUp() : void
    {
        $this->charsetConverter = new MbWrapperService();
    }

    public function testNameEmail() : void
    {
        $name = 'Julius Caeser';
        $email = 'gaius@altavista.com';
        $part = new AddressPart($this->charsetConverter, $name, $email);
        $this->assertEquals($name, $part->getName());
        $this->assertEquals($email, $part->getEmail());
    }
}
