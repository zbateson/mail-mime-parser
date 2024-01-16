<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

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
    // @phpstan-ignore-next-line
    private $charsetConverter;

    protected function setUp() : void
    {
        $this->charsetConverter = new MbWrapper();
    }

    public function testBasicNameValuePair() : void
    {
        $part = new ParameterPart($this->charsetConverter, 'Name', 'Value');
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }

    public function testMimeValue() : void
    {
        $part = new ParameterPart($this->charsetConverter, 'name', '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals('name', $part->getName());
        $this->assertEquals('Kilgore Trout', $part->getValue());
    }

    public function testMimeName() : void
    {
        $part = new ParameterPart($this->charsetConverter, '=?US-ASCII?Q?name?=', 'Kilgore');
        $this->assertEquals('name', $part->getName());
        $this->assertEquals('Kilgore', $part->getValue());
    }

    public function testNameValueNotDecodedWithLanguage() : void
    {
        $part = new ParameterPart($this->charsetConverter, '=?US-ASCII?Q?name?=', '=?US-ASCII?Q?Kilgore_Trout?=', 'Kurty');
        $this->assertEquals('=?US-ASCII?Q?name?=', $part->getName());
        $this->assertEquals('=?US-ASCII?Q?Kilgore_Trout?=', $part->getValue());
    }

    public function testGetLanguage() : void
    {
        $part = new ParameterPart($this->charsetConverter, 'name', 'Drogo', 'Dothraki');
        $this->assertEquals('Dothraki', $part->getLanguage());
    }

    public function testValidation() : void
    {
        $part = new ParameterPart($this->charsetConverter, 'name', '');
        $errs = $part->getErrors(true, LogLevel::NOTICE);
        $this->assertCount(1, $errs);
        $this->assertEquals('Parameter part value is empty', $errs[0]->getMessage());
        $this->assertEquals(LogLevel::NOTICE, $errs[0]->getPsrLevel());
    }
}
