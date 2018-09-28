<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;

/**
 * Description of MultipleIdHeaderTest
 *
 * @group Headers
 * @group IdHeader
 * @covers ZBateson\MailMimeParser\Header\MultipleIdHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class MultipleIdHeaderTest extends TestCase
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

    public function testGetIds()
    {
        $header = new MultipleIdHeader($this->consumerService, 'Reference', ' <1337@example.com> <7331@example.com> <4@example.com> ');
        $this->assertEquals('<1337@example.com> <7331@example.com> <4@example.com>', $header->getValue());
        $this->assertEquals([ '1337@example.com', '7331@example.com', '4@example.com' ], $header->getIds());
    }

    public function testGetIdsWithInvalidValue()
    {
        $header = new MultipleIdHeader($this->consumerService, 'In-Reply-To', 'Blah Blah');
        $this->assertEquals('Blah Blah', $header->getValue());
        $this->assertEquals(['Blah Blah'], $header->getIds());
    }

    public function testGetIdsWithEmptyValue()
    {
        $header = new MultipleIdHeader($this->consumerService, 'Reference', '');
        $this->assertEquals('', $header->getValue());
        $this->assertEquals([], $header->getIds());
    }
}
