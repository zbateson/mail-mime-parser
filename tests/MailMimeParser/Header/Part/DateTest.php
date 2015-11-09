<?php

use ZBateson\MailMimeParser\Header\Part\Date;

/**
 * Description of DateTest
 *
 * @group HeaderParts
 * @group Date
 * @author Zaahid Bateson
 */
class DateTest extends PHPUnit_Framework_TestCase
{
    public function testDateString()
    {
        $value = 'Wed, 17 May 2000 19:08:29 -0400';
        $part = new Date($value);
        $this->assertEquals($value, $part->getValue());
        $date = $part->getDateTime();
        $this->assertNotEmpty($date);
        $this->assertEquals($value, $date->format(DateTime::RFC2822));
    }
}
