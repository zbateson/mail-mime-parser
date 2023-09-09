<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;

/**
 * Description of HeaderPartFactoryTest
 *
 * @group HeaderParts
 * @group HeaderPartFactory
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPartFactory
 * @author Zaahid Bateson
 */
class HeaderPartFactoryTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $headerPartFactory;

    protected function setUp() : void
    {
        $charsetConverter = new MbWrapperService();
        $this->headerPartFactory = new HeaderPartFactory($charsetConverter);
    }

    public function testIsService() : void
    {
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Container\IService::class, $this->headerPartFactory);
    }

    public function testNewInstance() : void
    {
        $token = $this->headerPartFactory->newInstance('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\Token::class, $token);
    }

    public function testNewToken() : void
    {
        $token = $this->headerPartFactory->newToken('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\Token::class, $token);
    }

    public function testNewSplitParameterToken() : void
    {
        $token = $this->headerPartFactory->newSplitParameterToken('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\SplitParameterToken::class, $token);
    }

    public function testNewLiteralPart() : void
    {
        $part = $this->headerPartFactory->newLiteralPart('Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $part);
    }

    public function testNewMimeLiteralPart() : void
    {
        $part = $this->headerPartFactory->newMimeLiteralPart('Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\MimeLiteralPart::class, $part);
    }

    public function testNewCommentPart() : void
    {
        $part = $this->headerPartFactory->newCommentPart('Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\CommentPart::class, $part);
    }

    public function testNewAddressPart() : void
    {
        $part = $this->headerPartFactory->newAddressPart('Test', 'Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $part);
    }

    public function testNewAddressGroupPart() : void
    {
        $part = $this->headerPartFactory->newAddressGroupPart(['Test']);
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressGroupPart::class, $part);
    }

    public function testNewDatePart() : void
    {
        $part = $this->headerPartFactory->newDatePart('Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\DatePart::class, $part);
    }

    public function testNewParameterPart() : void
    {
        $part = $this->headerPartFactory->newParameterPart('Test', 'Value');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ParameterPart::class, $part);
    }

    public function testNewReceivedPart() : void
    {
        $part = $this->headerPartFactory->newReceivedPart('Test', 'Value');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ReceivedPart::class, $part);
        $this->assertEquals('Test', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }

    public function testNewReceivedDomainPart() : void
    {
        $part = $this->headerPartFactory->newReceivedDomainPart('Test', 'Value', 'ehlo', 'host', 'addr');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart::class, $part);
        $this->assertEquals('Test', $part->getName());
        $this->assertEquals('Value', $part->getValue());
        $this->assertEquals('ehlo', $part->getEhloName());
        $this->assertEquals('host', $part->getHostname());
        $this->assertEquals('addr', $part->getAddress());
    }
}
