<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit_Framework_TestCase;
use DateTime;

/**
 * Description of DateTest
 *
 * @group HeaderParts
 * @group DatePart
 * @covers ZBateson\MailMimeParser\Header\Part\DatePart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class DatePartTest extends PHPUnit_Framework_TestCase
{
    public function testDateString()
    {
        $value = 'Wed, 17 May 2000 19:08:29 -0400';
        $part = new DatePart($value);
        $this->assertEquals($value, $part->getValue());
        $date = $part->getDateTime();
        $this->assertNotEmpty($date);
        $this->assertEquals($value, $date->format(DateTime::RFC2822));
    }
    
    public function testInvalidDate()
    {
        $value = 'Invalid Date';
        $part = new DatePart($value);
        $this->assertEquals($value, $part->getValue());
        $date = $part->getDateTime();
        $this->assertNull($date);
    }
}
