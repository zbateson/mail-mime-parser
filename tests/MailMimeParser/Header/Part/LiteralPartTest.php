<?php
namespace ZBateson\MailMimeParser\Header\Part;

use LegacyPHPUnit\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of LiteralTest
 *
 * @group HeaderParts
 * @group LiteralPart
 * @covers ZBateson\MailMimeParser\Header\Part\LiteralPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class LiteralPartTest extends TestCase
{
    public function testInstance()
    {
        $charsetConverter = new MbWrapper();

        $part = new LiteralPart($charsetConverter, '"');
        $this->assertNotNull($part);
        $this->assertEquals('"', $part->getValue());

        $part = new LiteralPart($charsetConverter, '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals('=?US-ASCII?Q?Kilgore_Trout?=', $part->getValue());
    }
}
