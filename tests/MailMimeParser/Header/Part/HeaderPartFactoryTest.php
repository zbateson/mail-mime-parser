<?php

use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;

/**
 * Description of HeaderPartFactoryTest
 *
 * @group HeaderParts
 * @group HeaderPartFactory
 * @author Zaahid Bateson
 */
class HeaderPartFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $headerPartFactory;
    
    public function setUp()
    {
        $this->headerPartFactory = new HeaderPartFactory();
    }
    
    public function testNewToken()
    {
        $token = $this->headerPartFactory->newToken('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\Token', $token);
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
        $part = $this->headerPartFactory->newParameterPart('Test', 'Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ParameterPart', $part);
    }
}
