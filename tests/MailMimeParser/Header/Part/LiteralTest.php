<?php

use ZBateson\MailMimeParser\Header\Part\Literal;
use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * Description of LiteralTest
 *
 * @group HeaderParts
 * @group Literal
 * @author Zaahid Bateson
 */
class LiteralTest extends \PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $part = new Literal('"');
        $this->assertNotNull($part);
        $this->assertEquals('"', $part->getValue());
        
        $part = new Literal(new Token('=?US-ASCII?Q?Kilgore_Trout?='));
        $this->assertEquals('=?US-ASCII?Q?Kilgore_Trout?=', $part->getValue());
    }
}
