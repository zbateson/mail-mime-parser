<?php

use ZBateson\MailMimeParser\Header\Part\LiteralPart;
use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * Description of LiteralTest
 *
 * @group HeaderParts
 * @group LiteralPart
 * @author Zaahid Bateson
 */
class LiteralPartTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $part = new LiteralPart('"');
        $this->assertNotNull($part);
        $this->assertEquals('"', $part->getValue());
        
        $part = new LiteralPart(new Token('=?US-ASCII?Q?Kilgore_Trout?='));
        $this->assertEquals('=?US-ASCII?Q?Kilgore_Trout?=', $part->getValue());
    }
}
