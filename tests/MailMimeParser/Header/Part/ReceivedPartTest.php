<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\TestCase;

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
    // @phpstan-ignore-next-line
    private $charsetConverter;

    protected function setUp() : void
    {
        $this->charsetConverter = new MbWrapper();
    }

    public function testBasicNameValuePair() : void
    {
        $part = new ReceivedPart($this->charsetConverter, 'Name', 'Value');
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }
}
