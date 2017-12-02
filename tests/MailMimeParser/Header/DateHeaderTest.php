<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit_Framework_TestCase;

/**
 * Description of DateHeaderTest
 *
 * @group Headers
 * @group DateHeader
 * @covers ZBateson\MailMimeParser\Header\DateHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class DateHeaderTest extends PHPUnit_Framework_TestCase
{
    protected $consumerService;
    
    protected function setUp()
    {
        $charsetConverter = $this->getMock('ZBateson\MailMimeParser\Util\CharsetConverter', ['__toString']);
        $pf = $this->getMock('ZBateson\MailMimeParser\Header\Part\HeaderPartFactory', ['__toString'], [$charsetConverter]);
        $mlpf = $this->getMock('ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory', ['__toString'], [$charsetConverter]);
        $this->consumerService = $this->getMock('ZBateson\MailMimeParser\Header\Consumer\ConsumerService', ['__toString'], [$pf, $mlpf]);
    }
    
    public function testSimpleDate()
    {
        $header = new DateHeader($this->consumerService, 'Date', 'Wed, 17 May 2000 19:08:29 -0400');
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $header->getValue());
        $dt = $header->getDateTime();
        $this->assertNotNull($dt);
        $this->assertEquals('Wed, 17 May 2000 19:08:29 -0400', $dt->format(\DateTime::RFC2822));
    }
    
    public function testInvalidDate()
    {
        $header = new DateHeader($this->consumerService, 'DATE', 'This is not a date');
        $this->assertNull($header->getDateTime());
        $this->assertEquals('This is not a date', $header->getValue());
    }
    
    public function testDateWithEmptyPart()
    {
        $header = new DateHeader($this->consumerService, 'DATE', '');
        $this->assertNull($header->getDateTime());
    }
    
    public function testDateHeaderToString()
    {
        $header = new DateHeader($this->consumerService, 'Date', 'Wed, 17 May 2000 19:08:29 -0400');
        $this->assertEquals('Date: Wed, 17 May 2000 19:08:29 -0400', $header);
    }
}
