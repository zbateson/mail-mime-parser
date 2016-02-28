<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

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
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $this->consumerService = new ConsumerService($pf, $mlpf);
    }
    
    public function testSimpleDate()
    {
        $header = new DateHeader($this->consumerService, 'Date', 'Wed, 17 May 2000 19:08:29 -0400');
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $header->getValue());
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $header->getDateTime()->format(\DateTime::RFC2822));
    }
    
    public function testInvalidDate()
    {
        $header = new DateHeader($this->consumerService, 'DATE', 'This is not a date');
        $this->assertNull($header->getDateTime());
        $this->assertEquals('This is not a date', $header->getValue());
    }
}
