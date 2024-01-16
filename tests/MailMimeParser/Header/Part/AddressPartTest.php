<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

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
        $this->charsetConverter = new MbWrapper();
    }

    public function testNameEmail() : void
    {
        $name = 'Julius Caeser';
        $email = 'gaius@altavista.com';
        $part = new AddressPart($this->charsetConverter, $name, $email);
        $this->assertEquals($name, $part->getName());
        $this->assertEquals($email, $part->getEmail());
    }

    public function testValidation() : void
    {
        $part = new AddressPart($this->charsetConverter, '', '');
        $errs = $part->getErrors(true, LogLevel::ERROR);
        $this->assertCount(1, $errs);
        $this->assertEquals('AddressPart doesn\'t contain an email address', $errs[0]->getMessage());
        $this->assertEquals(LogLevel::ERROR, $errs[0]->getPsrLevel());
    }
}
