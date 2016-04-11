<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of ParametersHeaderTest
 *
 * @group Headers
 * @group ParameterHeader
 * @covers ZBateson\MailMimeParser\Header\ParameterHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class ParameterHeaderTest extends PHPUnit_Framework_TestCase
{
    protected $consumerService;
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $this->consumerService = new ConsumerService($pf, $mlpf);
    }
    
    public function testParsingContentType()
    {
        $header = new ParameterHeader($this->consumerService, 'Content-Type', 'text/html; CHARSET="utf-8"');
        $this->assertEquals('text/html', $header->getValue());
        $this->assertEquals('utf-8', $header->getValueFor('charset'));
    }
    
    public function testParsingMultipleParts()
    {
        $header = new ParameterHeader($this->consumerService, 'Content-Type', 'TEXT/html; CHARSET=utf-8; Boundary="blooh";answer-to-everything=42');
        $this->assertEquals('TEXT/html', $header->getValue());
        $this->assertEquals('utf-8', $header->getValueFor('charset'));
        $this->assertEquals('blooh', $header->getValueFor('boundary'));
        $this->assertEquals('42', $header->getValueFor('answer-to-everything'));
    }
    
    public function testDefaultParameterValue()
    {
        $header = new ParameterHeader($this->consumerService, 'Content-Type', 'text/html; CHARSET="utf-8"');
        $this->assertEquals(null, $header->getValueFor('boundary'));
        $this->assertEquals('default', $header->getValueFor('test', 'default'));
    }
    
    public function testParameterHeaderToString()
    {
        $header = new ParameterHeader($this->consumerService, 'Content-Type', 'text/html; CHARSET="utf-8"');
        $this->assertEquals('Content-Type: text/html; CHARSET="utf-8"', $header);
    }
}
