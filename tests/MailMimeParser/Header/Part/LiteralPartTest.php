<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of LiteralTest
 *
 * @group HeaderParts
 * @group LiteralPart
 * @covers ZBateson\MailMimeParser\Header\Part\LiteralPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
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
