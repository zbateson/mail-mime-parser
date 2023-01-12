<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;

/**
 * Description of ParametersHeaderTest
 *
 * @group Headers
 * @group ParameterHeader
 * @covers ZBateson\MailMimeParser\Header\ParameterHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class ParameterHeaderTest extends TestCase
{
    protected $consumerService;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder('ZBateson\MbWrapper\MbWrapper')
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\HeaderPartFactory')
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mlpf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory')
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $this->consumerService = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Consumer\ConsumerService')
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
    }

    public function testParsingContentTypeWithoutParameters()
    {
        $header = new ParameterHeader($this->consumerService, 'Content-Type', 'text/html');
        $this->assertEquals('text/html', $header->getValue());
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

    public function testParsingHeaderWithNoValue()
    {
        $header = new ParameterHeader($this->consumerService, 'Autocrypt', 'addr=brosif@example.com; keydata=example');
        $this->assertEquals('brosif@example.com', $header->getValue());
        $this->assertEquals('brosif@example.com', $header->getValueFor('addr'));
        $this->assertEquals('example', $header->getValueFor('keydata'));
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
