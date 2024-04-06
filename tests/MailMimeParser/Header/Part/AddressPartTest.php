<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
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
    // @phpstan-ignore-next-line
    private $mb;
    private $hpf;

    protected function setUp() : void
    {
        $this->mb = new MbWrapper();
        $this->hpf = $this->getMockBuilder(HeaderPartFactory::class)
            ->setConstructorArgs([$this->mb])
            ->setMethods(['__toString'])
            ->getMock();
    }

    private function getTokenMock(string $name) : Token
    {
        return $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$this->mb, $name])
            ->setMethods()
            ->getMock();
    }

    public function testNameEmail() : void
    {
        $name = 'Julius Caeser';
        $email = 'gaius@altavista.com';
        $part = new AddressPart($this->mb, $this->hpf, [$this->getTokenMock($name)], [$this->getTokenMock($email)]);
        $this->assertEquals($name, $part->getName());
        $this->assertEquals($email, $part->getEmail());
    }

    public function testValidation() : void
    {
        $part = new AddressPart($this->mb, $this->hpf, [], []);
        $errs = $part->getErrors(true, LogLevel::ERROR);
        $this->assertCount(1, $errs);
        $this->assertEquals('AddressPart doesn\'t contain an email address', $errs[0]->getMessage());
        $this->assertEquals(LogLevel::ERROR, $errs[0]->getPsrLevel());
    }
}
