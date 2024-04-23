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

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $this->mb = new MbWrapper();
        $this->hpf = $this->getMockBuilder(HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $this->mb])
            ->setMethods()
            ->getMock();
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

    private function getQuotedMock(string $name) : QuotedLiteralPart
    {
        return $this->getMockBuilder(QuotedLiteralPart::class)
            ->setConstructorArgs([$this->logger, $this->mb, [$this->getTokenMock($name)]])
            ->setMethods()
            ->getMock();
    }

    private function getCommentMock(string $name) : CommentPart
    {
        return $this->getMockBuilder(CommentPart::class)
            ->setConstructorArgs([$this->logger, $this->mb, $this->hpf, [$this->getTokenMock($name)]])
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

    public function testEmailWithQuotedParts() : void
    {
        $email = 'gaius" julius "caesar@altavista.com';
        $part = $this->newAddressPart([], [$this->getTokenMock('gaius '), $this->getQuotedMock(' julius '), $this->getTokenMock(' caesa r@altavista.com')]);
        $this->assertEquals($email, $part->getEmail());
    }

    public function testEmailWithCommentsAndQuotedParts() : void
    {
        $email = 'gaius"julius"caesar@altavista.com';
        $part = $this->newAddressPart([], [
            $this->getTokenMock('gaius '),
            $this->getQuotedMock('julius'),
            $this->getTokenMock('caesar'),
            $this->getCommentMock('emperor, innit'),
            $this->getTokenMock('@altavista.com')
        ]);
        $this->assertEquals($email, $part->getEmail());
        $comments = $part->getComments();
        $this->assertNotEmpty($comments);
        $this->assertCount(1, $comments);
        $this->assertEquals('emperor, innit', $comments[0]->getComment());
    }

    public function testValidation() : void
    {
        $part = $this->newAddressPart([], []);
        $errs = $part->getErrors(true, LogLevel::ERROR);
        $this->assertCount(1, $errs);
        $this->assertNotEmpty($errs[0]->getMessage());
        $this->assertEquals(LogLevel::ERROR, $errs[0]->getPsrLevel());
    }
}
