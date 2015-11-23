<?php

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\DateHeader;

/**
 * Description of DateHeaderTest
 *
 * @group Headers
 * @group DateHeader
 * @author Zaahid Bateson
 */
class DateHeaderTest extends PHPUnit_Framework_TestCase
{
    protected $consumerService;
    
    public function setUp()
    {
        $pf = new HeaderPartFactory();
        $this->consumerService = new ConsumerService($pf);
    }
    
    /*public function testInstance()
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
    }*/
    
    public function testSimpleDate()
    {
        $header = new DateHeader($this->consumerService, 'Date', 'Wed, 17 May 2000 19:08:29 -0400');
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $header->getValue());
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $header->getDateTime()->format(\DateTime::RFC2822));
    }
    
    public function testInvalidDate()
    {
        $header = new DateHeader($this->consumerService, 'DATE', 'This is not a date');
        $this->assertFalse($header->getDateTime());
        $this->assertEquals('This is not a date', $header->getValue());
    }
}
