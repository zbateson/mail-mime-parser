<?php
use ZBateson\MailMimeParser\SimpleDi as SimpleDi;

/**
 * Description of DateHeaderTest
 *
 * @group Headers
 * @group DateHeader
 * @author Zaahid Bateson
 */
class DateHeaderTest extends \PHPUnit_Framework_TestCase
{
    protected $headerFactory;
    
    public function setup()
    {
        $di = SimpleDi::singleton();
        $this->headerFactory = $di->getHeaderFactory();
    }
    
    public function testInstance()
    {
        $aValid = ['Date', 'ExpIRY-Date', 'EXPIRES'];
        $aNot = ['MESSAGE-ID', 'bcc', 'Subject'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Wed, 17 May 2000 19:08:29 -0400');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\DateHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\DateHeader', get_class($header));
        }
    }
    
    public function testParsingDate()
    {
        $header = $this->headerFactory->newInstance('Date', 'Wed, 17 May 2000 19:08:29 -0400');
        $this->assertNotNull($header);
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $header->value);
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $header->date->format(\DateTime::RFC2822));
    }
    
    public function testParsingNonDate()
    {
        $header = $this->headerFactory->newInstance('DATE', 'This is not a date');
        $this->assertNotNull($header);
        $this->assertFalse($header->date);
        $this->assertEquals('This is not a date', $header->value);
    }
}
