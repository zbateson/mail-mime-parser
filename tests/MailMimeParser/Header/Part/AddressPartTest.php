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
    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $this->mb = new MbWrapper();
    }

    private function newAddressPart($nameParts, $valueParts)
    {
        return new AddressPart($this->logger, $this->mb, $nameParts, $valueParts);
    }

    private function getTokenMock(string $name) : Token
    {
        return $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$this->logger, $this->mb, $name])
            ->setMethods()
            ->getMock();
    }

    public function testNameEmail() : void
    {
        $name = 'Julius Caeser';
        $email = 'gaius@altavista.com';
        $part = $this->newAddressPart([$this->getTokenMock($name)], [$this->getTokenMock($email)]);
        $this->assertEquals($name, $part->getName());
        $this->assertEquals($email, $part->getEmail());
    }

    public function testValidation() : void
    {
        $part = $this->newAddressPart([], []);
        $errs = $part->getErrors(true, LogLevel::ERROR);
        $this->assertCount(1, $errs);
        $this->assertEquals('Address doesn\'t contain an email address', $errs[0]->getMessage());
        $this->assertEquals(LogLevel::ERROR, $errs[0]->getPsrLevel());
    }
}
