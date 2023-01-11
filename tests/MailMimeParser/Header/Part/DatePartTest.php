<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of DateTest
 *
 * @group HeaderParts
 * @group DatePart
 * @covers ZBateson\MailMimeParser\Header\Part\DatePart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class DatePartTest extends TestCase
{
    private $charsetConverter;

    protected function setUp() : void
    {
        $this->charsetConverter = new MbWrapper();
    }

    public function testDateString()
    {
        $values = [
            ['2000-05-17T19:08:29-0400', 'Wed, 17 May 2000 19:08:29 -0400'],
            ['2014-03-13T15:02:47+0000', 'Thu, 13 Mar 14 15:02:47 +0000'],
            ['1999-05-06T15:02:47+0000', 'Thu, 6 May 99 15:02:47 +0000'],
            ['1999-05-06T15:02:47+0000', 'Thu, 6 May 1999 15:02:47 UT'],
            ['2014-03-13T15:02:47+0000', 'Thu, 13 Mar 2014 15:02:47 0000'] // Not RFC-compliant
        ];

        foreach ($values as $value) {
            list($expected, $raw) = $value;
            $part = new DatePart($this->charsetConverter, $raw);
            $this->assertEquals($raw, $part->getValue(), 'Testing ' . $raw);
            $this->assertNotEmpty($part->getDateTime(), 'Testing ' . $raw);
            $this->assertEquals($expected, $part->getDateTime()->format(\DateTime::ISO8601), 'Testing ' . $raw);
        }
    }

    public function testInvalidDate()
    {
        $value = 'Invalid Date';
        $part = new DatePart($this->charsetConverter, $value);
        $this->assertEquals($value, $part->getValue());
        $date = $part->getDateTime();
        $this->assertNull($date);
    }
}
