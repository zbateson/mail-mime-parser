<?php

use ZBateson\MailMimeParser\MimePart;

/**
 * Description of MimePartTest
 *
 * @group MimePart
 * @author Zaahid Bateson
 */
class MimePartTest extends PHPUnit_Framework_TestCase
{
    private $mockHeaderFactory;
    
    public function setUp()
    {
        $this->mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
    }
    
    public function tearDown()
    {
        unset($this->mockHeaderFactory);
    }
    
    protected function getMockedParameterHeader($name, $value)
    {
        $header = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'getName', 'hasParameter'])
            ->getMock();
        $header->method('getName')->willReturn($name);
        $header->method('getValue')->willReturn($value);
        $header->method('hasParameter')->willReturn(true);
        return $header;
    }

    public function testAttachContentResourceHandle()
    {
        $hf = $this->mockHeaderFactory;
        $part = new MimePart($hf);

        $this->assertFalse($part->hasContent());
        $res = fopen('php://memory', 'rw');

        $part->attachContentResourceHandle($res);
        $this->assertTrue($part->hasContent());
        $this->assertSame($res, $part->getContentResourceHandle());
    }

    public function testSetRawHeader()
    {
        $hf = $this->mockHeaderFactory;
        $firstHeader = $this->getMockedParameterHeader('First-Header', 'Value');
        $secondHeader = $this->getMockedParameterHeader('Second-Header', 'Second Value');
        
        $hf->expects($this->exactly(2))
            ->method('newInstance')
            ->withConsecutive(
                [$firstHeader->getName(), $firstHeader->getValue()],
                [$secondHeader->getName(), $secondHeader->getValue()]
            )
            ->willReturnOnConsecutiveCalls($firstHeader, $secondHeader);
        
        $part = new MimePart($hf);
        $part->setRawHeader($firstHeader->getName(), $firstHeader->getValue());
        $part->setRawHeader($secondHeader->getName(), $secondHeader->getValue());
        $this->assertSame($firstHeader, $part->getHeader($firstHeader->getName()));
        $this->assertSame($secondHeader, $part->getHeader($secondHeader->getName()));
        $this->assertEquals($firstHeader->getValue(), $part->getHeaderValue($firstHeader->getName()));
        $this->assertEquals($secondHeader->getValue(), $part->getHeaderValue($secondHeader->getName()));
        $this->assertCount(2, $part->getHeaders());
    }
    
    public function testHeaderCaseInsensitive()
    {
        $hf = $this->mockHeaderFactory;
        $firstHeader = $this->getMockedParameterHeader('First-Header', 'Value');
        $secondHeader = $this->getMockedParameterHeader('Second-Header', 'Second Value');
        $thirdHeader = $this->getMockedParameterHeader('FIRST-header', 'Third Value');
        
        $hf->expects($this->exactly(3))
            ->method('newInstance')
            ->withConsecutive(
                [$firstHeader->getName(), $firstHeader->getValue()],
                [$secondHeader->getName(), $secondHeader->getValue()],
                [$thirdHeader->getName(), $thirdHeader->getValue()]
            )
            ->willReturnOnConsecutiveCalls($firstHeader, $secondHeader, $thirdHeader);
        
        $part = new MimePart($hf);
        $part->setRawHeader($firstHeader->getName(), $firstHeader->getValue());
        $part->setRawHeader($secondHeader->getName(), $secondHeader->getValue());
        $part->setRawHeader($thirdHeader->getName(), $thirdHeader->getValue());
        
        $this->assertSame($thirdHeader, $part->getHeader($firstHeader->getName()));
        $this->assertSame($secondHeader, $part->getHeader($secondHeader->getName()));
        $this->assertCount(2, $part->getHeaders());
    }
    
    public function testParent()
    {
        $hf = $this->mockHeaderFactory;
        $part = new MimePart($hf);
        $parent = new MimePart($hf);
        $part->setParent($parent);
        $this->assertSame($parent, $part->getParent());
    }
}
