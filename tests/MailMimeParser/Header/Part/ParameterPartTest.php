<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;

/**
 * Description of ParameterTest
 *
 * @group HeaderParts
 * @group ParameterPart
 * @covers ZBateson\MailMimeParser\Header\Part\ParameterPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class ParameterPartTest extends TestCase
{
    private $charsetConverter;

    public function setUp()
    {
        $this->charsetConverter = $this->getMock('ZBateson\StreamDecorators\Util\CharsetConverter');
    }

    public function testBasicNameValuePair()
    {
        $part = new ParameterPart($this->charsetConverter, 'Name', 'Value');
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }

    public function testMimeValue()
    {
        $this->charsetConverter->expects($this->once())
            ->method('convert')
            ->with('Kilgore Trout', 'US-ASCII', 'UTF-8')
            ->willReturn('Kilgore Trout');
        $part = new ParameterPart($this->charsetConverter, 'name', '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals('name', $part->getName());
        $this->assertEquals('Kilgore Trout', $part->getValue());
    }

    public function testMimeName()
    {
        $this->charsetConverter->expects($this->once())
            ->method('convert')
            ->with('name', 'US-ASCII', 'UTF-8')
            ->willReturn('name');
        $part = new ParameterPart($this->charsetConverter, '=?US-ASCII?Q?name?=', 'Kilgore');
        $this->assertEquals('name', $part->getName());
        $this->assertEquals('Kilgore', $part->getValue());
    }

    public function testNameValueNotDecodedWithLanguage()
    {
        $this->charsetConverter->expects($this->never())
            ->method('convert');
        $part = new ParameterPart($this->charsetConverter, '=?US-ASCII?Q?name?=', '=?US-ASCII?Q?Kilgore_Trout?=', 'Kurty');
        $this->assertEquals('=?US-ASCII?Q?name?=', $part->getName());
        $this->assertEquals('=?US-ASCII?Q?Kilgore_Trout?=', $part->getValue());
    }

    public function testGetLanguage()
    {
        $this->charsetConverter->expects($this->never())
            ->method('convert');
        $part = new ParameterPart($this->charsetConverter, 'name', 'Drogo', 'Dothraki');
        $this->assertEquals('Dothraki', $part->getLanguage());
    }
}
