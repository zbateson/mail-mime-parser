<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;

class MimeEncodedHeaderImpl extends MimeEncodedHeader
{
    protected function getConsumer(ConsumerService $consumerService) : AbstractConsumer
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
    // @phpstan-ignore-next-line
    protected $consumerService;

    // @phpstan-ignore-next-line
    protected $mimeLiteralPartFactory;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
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
        $this->mimeLiteralPartFactory = $mlpf;
    }

    private function newMimeEncodedHeader($name, $value) : MimeEncodedHeaderImpl
    {
        return new MimeEncodedHeaderImpl(
            $this->mimeLiteralPartFactory,
            $this->consumerService,
            $name,
            $value
        );
    }

    public function testGetDecoded() : void
    {
        $header = $this->newMimeEncodedHeader('Test', '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals('Kilgore Trout', $header->getValue());
    }

    public function testMultipleDecoded() : void
    {
        $header = $this->newMimeEncodedHeader(
            'Test',
            "=?US-ASCII?Q?Kilgore_?= =?US-ASCII?Q?Tro?=\r\n =?US-ASCII?Q?ut?="
        );
        $this->assertEquals('Kilgore  Tro ut', $header->getValue());
    }

    public function testDecodeWhenMixed() : void
    {
        $t = '=?US-ASCII?Q?Kilgore_?= TEST =?US-ASCII?Q?Tro?= =?US-ASCII?Q?ut?=';
        $header = $this->newMimeEncodedHeader(
            'Test',
            $t
        );
        $this->assertEquals('Kilgore  TEST Tro ut', $header->getValue());
    }
}
