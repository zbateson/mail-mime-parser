<?php

use ZBateson\MailMimeParser\Header\Part\Parameter;

/**
 * Description of ParameterTest
 *
 * @group HeaderParts
 * @group Parameter
 * @author Zaahid Bateson
 */
class ParameterTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicNameValuePair()
    {
        $part = new Parameter('Name', 'Value');
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }
    
    public function testMimeValue()
    {
        $part = new Parameter('name', '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals('name', $part->getName());
        $this->assertEquals('Kilgore Trout', $part->getValue());
    }
    
    public function testMimeName()
    {
        $part = new Parameter('=?US-ASCII?Q?name?=', 'Kilgore');
        $this->assertEquals('name', $part->getName());
        $this->assertEquals('Kilgore', $part->getValue());
    }
}
