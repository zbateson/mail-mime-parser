<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;

/**
 * Description of IdHeaderTest
 *
 * @group Headers
 * @group IdHeader
 * @covers ZBateson\MailMimeParser\Header\IdHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class IdHeaderTest extends TestCase
{
    protected $consumerService;

    protected function setUp()
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

    public function testGetId()
    {
        $header = new IdHeader($this->consumerService, 'Content-ID', ' <1337@example.com> ');
        $this->assertEquals('1337@example.com', $header->getValue());
    }

    public function testGetIdWithInvalidId()
    {
        $header = new IdHeader($this->consumerService, 'Content-ID', 'Test');
        $this->assertEquals('Test', $header->getValue());
    }

    public function testGetIdWithEmptyValue()
    {
        $header = new IdHeader($this->consumerService, 'Content-ID', '');
        $this->assertNull($header->getValue());
        $this->assertEquals([], $header->getIds());
    }

    public function testGetIds()
    {
        $header = new IdHeader($this->consumerService, 'References', ' <1337@example.com> <7331@example.com> <4@example.com> ');
        $this->assertEquals('1337@example.com', $header->getValue());
        $this->assertEquals([ '1337@example.com', '7331@example.com', '4@example.com' ], $header->getIds());
    }

    public function testGetIdsWithComments()
    {
        $header = new IdHeader($this->consumerService, 'References', '(blah)<1337@example(test).com>(wha<asdf>t!)<"7331"@example.com><4(test)@example.com> ');
        $this->assertEquals('1337@example.com', $header->getValue());
        $this->assertEquals([ '1337@example.com', '7331@example.com', '4@example.com' ], $header->getIds());
    }

    public function testGetIdsWithInvalidValue()
    {
        $header = new IdHeader($this->consumerService, 'In-Reply-To', 'Blah Blah');
        $this->assertEquals('Blah Blah', $header->getValue());
        $this->assertEquals(['Blah Blah'], $header->getIds());
    }
}
