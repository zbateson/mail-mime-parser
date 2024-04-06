<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;

class MimeEncodedHeaderImpl extends MimeEncodedHeader
{
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
    protected $mimeTokenPartFactory;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$pf])
            ->setMethods(['__toString'])
            ->getMock();
        $this->consumerService = $qscs;
        $this->mimeTokenPartFactory = $mpf;
    }

    private function newMimeEncodedHeader($name, $value) : MimeEncodedHeaderImpl
    {
        return new MimeEncodedHeaderImpl(
            $this->mimeTokenPartFactory,
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

    public function testDecodeInvalidCharset() : void
    {
        $header = $this->newMimeEncodedHeader('Test', '=?NAAHT-GOOD?Q?Kilgore_Trout?=');
        $this->assertEquals('Kilgore Trout', $header->getValue());
        $this->assertEquals(['Kilgore Trout'], \array_map(fn ($e) => $e->getObject()->getValue(), $header->getAllErrors()));
    }
}
