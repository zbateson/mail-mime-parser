<?php

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\ParameterHeader;

/**
 * Description of ParametersHeaderTest
 *
 * @group Headers
 * @group ParameterHeader
 * @author Zaahid Bateson
 */
class ParameterHeaderTest extends PHPUnit_Framework_TestCase
{
    protected $consumerService;
    
    public function setUp()
    {
        $pf = new HeaderPartFactory();
        $this->consumerService = new ConsumerService($pf);
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
}
