<?php

use ZBateson\MailMimeParser\Part;

/**
 * Description of PartTest
 *
 * @group Part
 * @author Zaahid Bateson
 */
class PartTest extends \PHPUnit_Framework_TestCase
{
    protected function getMockedHeaderFactory()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
    }

    protected function getMockedValueParametersHeader($name, $value)
    {
        $header = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ValueParametersHeader')
            ->disableOriginalConstructor()
            ->setMethods(['__get', '__isset'])
            ->getMock();
        $header->method('__get')->will($this->returnCallback(function ($arg) use ($name, $value) {
            if ($arg === 'name') {
                return $name;
            } elseif ($arg === 'value') {
                return $value;
            }
        }));
        return $header;
    }

    public function testAttachContentResourceHandle()
    {
        $hf = $this->getMockedHeaderFactory();
        $part = new Part($hf);

        $this->assertFalse($part->hasContent());
        $res = fopen('php://memory', 'rw');

        $part->attachContentResourceHandle($res);
        $this->assertTrue($part->hasContent());
        $this->assertSame($res, $part->getContentResourceHandle());
    }

    public function testSetRawHeader()
    {
        $hf = $this->getMockedHeaderFactory();
        $firstHeader = $this->getMockedValueParametersHeader('First-Header', 'Value');
        $secondHeader = $this->getMockedValueParametersHeader('Second-Header', 'Second Value');
        
        $hf->expects($this->exactly(2))
            ->method('newInstance')
            ->withConsecutive(
                [$firstHeader->name, $firstHeader->value],
                [$secondHeader->name, $secondHeader->value]
            )
            ->willReturnOnConsecutiveCalls($firstHeader, $secondHeader);
        
        $part = new Part($hf);
        $part->setRawHeader($firstHeader->name, $firstHeader->value);
        $part->setRawHeader($secondHeader->name, $secondHeader->value);
        $this->assertSame($firstHeader, $part->getHeader($firstHeader->name));
        $this->assertSame($secondHeader, $part->getHeader($secondHeader->name));
        $this->assertEquals($firstHeader->value, $part->getHeaderValue($firstHeader->name));
        $this->assertEquals($secondHeader->value, $part->getHeaderValue($secondHeader->name));
        $this->assertCount(2, $part->getHeaders());
    }
    
    public function testHeaderCaseInsensitive()
    {
        $hf = $this->getMockedHeaderFactory();
        $firstHeader = $this->getMockedValueParametersHeader('First-Header', 'Value');
        $secondHeader = $this->getMockedValueParametersHeader('Second-Header', 'Second Value');
        $thirdHeader = $this->getMockedValueParametersHeader('FIRST-header', 'Third Value');
        
        $hf->expects($this->exactly(3))
            ->method('newInstance')
            ->withConsecutive(
                [$firstHeader->name, $firstHeader->value],
                [$secondHeader->name, $secondHeader->value],
                [$thirdHeader->name, $thirdHeader->value]
            )
            ->willReturnOnConsecutiveCalls($firstHeader, $secondHeader, $thirdHeader);
        
        $part = new Part($hf);
        $part->setRawHeader($firstHeader->name, $firstHeader->value);
        $part->setRawHeader($secondHeader->name, $secondHeader->value);
        $part->setRawHeader($thirdHeader->name, $thirdHeader->value);
        
        $this->assertSame($thirdHeader, $part->getHeader($firstHeader->name));
        $this->assertSame($secondHeader, $part->getHeader($secondHeader->name));
        $this->assertCount(2, $part->getHeaders());
    }
    
    public function testParent()
    {
        $hf = $this->getMockedHeaderFactory();
        $part = new Part($hf);
        $parent = new Part($hf);
        $part->setParent($parent);
        $this->assertSame($parent, $part->getParent());
    }
}
