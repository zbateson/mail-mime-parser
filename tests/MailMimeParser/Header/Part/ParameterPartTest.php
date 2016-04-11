<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of ParameterTest
 *
 * @group HeaderParts
 * @group ParameterPart
 * @covers ZBateson\MailMimeParser\Header\Part\ParameterPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class ParameterPartTest extends PHPUnit_Framework_TestCase
{
    public function testBasicNameValuePair()
    {
        $part = new ParameterPart('Name', 'Value');
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }
    
    public function testMimeValue()
    {
        $part = new ParameterPart('name', '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals('name', $part->getName());
        $this->assertEquals('Kilgore Trout', $part->getValue());
    }
    
    public function testMimeName()
    {
        $part = new ParameterPart('=?US-ASCII?Q?name?=', 'Kilgore');
        $this->assertEquals('name', $part->getName());
        $this->assertEquals('Kilgore', $part->getValue());
    }
}
