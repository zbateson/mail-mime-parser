<?php
namespace ZBateson\MailMimeParser\Header\Part;

use LegacyPHPUnit\TestCase;
use ZBateson\MbWrapper\MbWrapper;

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
    private $headerPartFactory;

    protected function legacySetUp()
    {
        $charsetConverter = new MbWrapper();
        $this->headerPartFactory = new HeaderPartFactory($charsetConverter);
    }

    public function testNewInstance()
    {
        $token = $this->headerPartFactory->newInstance('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\Token', $token);
    }

    public function testNewToken()
    {
        $token = $this->headerPartFactory->newToken('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\Token', $token);
    }

    public function testNewSplitParameterToken()
    {
        $token = $this->headerPartFactory->newSplitParameterToken('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\SplitParameterToken', $token);
    }

    public function testNewLiteralPart()
    {
        $part = $this->headerPartFactory->newLiteralPart('Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\LiteralPart', $part);
    }

    public function testNewMimeLiteralPart()
    {
        $part = $this->headerPartFactory->newMimeLiteralPart('Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\MimeLiteralPart', $part);
    }

    public function testNewCommentPart()
    {
        $part = $this->headerPartFactory->newCommentPart('Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\CommentPart', $part);
    }

    public function testNewAddressPart()
    {
        $part = $this->headerPartFactory->newAddressPart('Test', 'Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressPart', $part);
    }

    public function testNewAddressGroupPart()
    {
        $part = $this->headerPartFactory->newAddressGroupPart(['Test']);
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressGroupPart', $part);
    }

    public function testNewDatePart()
    {
        $part = $this->headerPartFactory->newDatePart('Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\DatePart', $part);
    }

    public function testNewParameterPart()
    {
        $part = $this->headerPartFactory->newParameterPart('Test', 'Value');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ParameterPart', $part);
    }

    public function testNewReceivedPart()
    {
        $part = $this->headerPartFactory->newReceivedPart('Test', 'Value');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ReceivedPart', $part);
        $this->assertEquals('Test', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }

    public function testNewReceivedDomainPart()
    {
        $part = $this->headerPartFactory->newReceivedDomainPart('Test', 'Value', 'ehlo', 'host', 'addr');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart', $part);
        $this->assertEquals('Test', $part->getName());
        $this->assertEquals('Value', $part->getValue());
        $this->assertEquals('ehlo', $part->getEhloName());
        $this->assertEquals('host', $part->getHostname());
        $this->assertEquals('addr', $part->getAddress());
    }
}
