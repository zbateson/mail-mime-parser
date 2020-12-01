<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of ReceivedTest
 *
 * @group HeaderParts
 * @group ReceivedPart
 * @covers ZBateson\MailMimeParser\Header\Part\ReceivedPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class ReceivedPartTest extends TestCase
{
    private $charsetConverter;

    public function setUp(): void
    {
        $this->charsetConverter = new MbWrapper();
    }

    public function testBasicNameValuePair()
    {
        $part = new ReceivedPart($this->charsetConverter, 'Name', 'Value');
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }
}
