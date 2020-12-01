<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;

class MimeEncodedHeaderImpl extends MimeEncodedHeader {
    protected function getConsumer(ConsumerService $consumerService)
    {
        return $consumerService->getQuotedStringConsumer();
    }
}

/**
 * Description of MimeEncodedHeaderTest
 *
 * @group Headers
 * @group MimeEncodedHeader
 * @covers ZBateson\MailMimeParser\Header\MimeEncodedHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class MimeEncodedHeaderTest extends TestCase
{
    protected $consumerService;
    protected $mimeLiteralPartFactory;

    protected function setUp(): void
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
        $this->mimeLiteralPartFactory = $mlpf;
    }

    private function newMimeEncodedHeader($name, $value)
    {
        return new MimeEncodedHeaderImpl(
            $this->mimeLiteralPartFactory,
            $this->consumerService,
            $name,
            $value
        );
    }

    public function testGetDecoded()
    {
        $header = $this->newMimeEncodedHeader('Test', '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals('Kilgore Trout', $header->getValue());
    }

    public function testMultipleDecoded()
    {
        $header = $this->newMimeEncodedHeader(
            'Test',
            "=?US-ASCII?Q?Kilgore_?= =?US-ASCII?Q?Tro?=\r\n =?US-ASCII?Q?ut?="
        );
        $this->assertEquals('Kilgore Trout', $header->getValue());
    }

    public function testNotDecodedWhenMixed()
    {
        $t = "=?US-ASCII?Q?Kilgore_?= TEST =?US-ASCII?Q?Tro?= =?US-ASCII?Q?ut?=";
        $header = $this->newMimeEncodedHeader(
            'Test',
            $t
        );
        $this->assertEquals($t, $header->getValue());
    }
}
