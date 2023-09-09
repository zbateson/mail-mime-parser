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
    // @phpstan-ignore-next-line
    protected $consumerService;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MbWrapperService::class)
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mlpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $this->consumerService = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\ConsumerService::class)
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
    }

    public function testParsingContentTypeWithoutParameters() : void
    {
        $header = new ParameterHeader($this->consumerService, 'Content-Type', 'text/html');
        $this->assertEquals('text/html', $header->getValue());
    }

    public function testParsingContentType() : void
    {
        $header = new ParameterHeader($this->consumerService, 'Content-Type', 'text/html; CHARSET="utf-8"');
        $this->assertEquals('text/html', $header->getValue());
        $this->assertEquals('utf-8', $header->getValueFor('charset'));
    }

    public function testParsingMultipleParts() : void
    {
        $header = new ParameterHeader($this->consumerService, 'Content-Type', 'TEXT/html; CHARSET=utf-8; Boundary="blooh";answer-to-everything=42');
        $this->assertEquals('TEXT/html', $header->getValue());
        $this->assertEquals('utf-8', $header->getValueFor('charset'));
        $this->assertEquals('blooh', $header->getValueFor('boundary'));
        $this->assertEquals('42', $header->getValueFor('answer-to-everything'));
    }

    public function testParsingHeaderWithNoValue() : void
    {
        $header = new ParameterHeader($this->consumerService, 'Autocrypt', 'addr=brosif@example.com; keydata=example');
        $this->assertEquals('brosif@example.com', $header->getValue());
        $this->assertEquals('brosif@example.com', $header->getValueFor('addr'));
        $this->assertEquals('example', $header->getValueFor('keydata'));
    }

    public function testDefaultParameterValue() : void
    {
        $header = new ParameterHeader($this->consumerService, 'Content-Type', 'text/html; CHARSET="utf-8"');
        $this->assertEquals(null, $header->getValueFor('boundary'));
        $this->assertEquals('default', $header->getValueFor('test', 'default'));
    }

    public function testParameterHeaderToString() : void
    {
        $header = new ParameterHeader($this->consumerService, 'Content-Type', 'text/html; CHARSET="utf-8"');
        $this->assertEquals('Content-Type: text/html; CHARSET="utf-8"', $header);
    }
}
