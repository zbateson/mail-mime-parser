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
        $charsetConverter = $this->getMockBuilder('ZBateson\StreamDecorators\Util\CharsetConverter')
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
        $this->assertEquals('<1337@example.com>', $header->getValue());
        $this->assertEquals('1337@example.com', $header->getId());
    }

    public function testGetIdWithInvalidId()
    {
        $header = new IdHeader($this->consumerService, 'Content-ID', 'Test');
        $this->assertEquals('Test', $header->getId());
    }

    public function testGetIdWithEmptyValue()
    {
        $header = new IdHeader($this->consumerService, 'Content-ID', '');
        $this->assertEquals('', $header->getId());
    }
}
