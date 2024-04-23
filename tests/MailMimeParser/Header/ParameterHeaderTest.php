<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ParameterConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ParameterNameValueConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ParameterValueConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringMimeLiteralPartConsumerService;

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

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods()
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->setMethods()
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $qscs])
            ->setMethods()
            ->getMock();

        $qsmlpcs = $this->getMockBuilder(QuotedStringMimeLiteralPartConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->setMethods()
            ->getMock();
        $pvcs = $this->getMockBuilder(ParameterValueConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $ccs, $qsmlpcs])
            ->setMethods()
            ->getMock();
        $pnvcs = $this->getMockBuilder(ParameterNameValueConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $pvcs, $ccs, $qscs])
            ->setMethods()
            ->getMock();
        $this->consumerService = $this->getMockBuilder(ParameterConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $pnvcs, $ccs, $qscs])
            ->setMethods()
            ->getMock();
    }

    private function newParameterHeader($name, $value)
    {
        return new ParameterHeader($name, $value, $this->logger, $this->consumerService);
    }

    public function testParsingContentTypeWithoutParameters() : void
    {
        $header = $this->newParameterHeader('Content-Type', 'text/html');
        $this->assertEquals('text/html', $header->getValue());
    }

    public function testParsingContentType() : void
    {
        $header = $this->newParameterHeader('Content-Type', 'text/html; CHARSET="utf-8"');
        $this->assertEquals('text/html', $header->getValue());
        $this->assertEquals('utf-8', $header->getValueFor('charset'));
    }

    public function testParsingMultipleParts() : void
    {
        $header = $this->newParameterHeader('Content-Type', 'TEXT/html; CHARSET=utf-8; Boundary="blooh";answer-to-everything=42');
        $this->assertEquals('TEXT/html', $header->getValue());
        $this->assertEquals('utf-8', $header->getValueFor('charset'));
        $this->assertEquals('blooh', $header->getValueFor('boundary'));
        $this->assertEquals('42', $header->getValueFor('answer-to-everything'));
    }

    public function testParsingHeaderWithNoValue() : void
    {
        $header = $this->newParameterHeader('Autocrypt', 'addr=brosif@example.com; keydata=example');
        $this->assertEquals('brosif@example.com', $header->getValue());
        $this->assertEquals('brosif@example.com', $header->getValueFor('addr'));
        $this->assertEquals('example', $header->getValueFor('keydata'));
    }

    public function testDefaultParameterValue() : void
    {
        $header = $this->newParameterHeader('Content-Type', 'text/html; CHARSET="utf-8"');
        $this->assertEquals(null, $header->getValueFor('boundary'));
        $this->assertEquals('default', $header->getValueFor('test', 'default'));
    }

    public function testParameterHeaderToString() : void
    {
        $header = $this->newParameterHeader('Content-Type', 'text/html; CHARSET="utf-8"');
        $this->assertEquals('Content-Type: text/html; CHARSET="utf-8"', $header);
    }
}
