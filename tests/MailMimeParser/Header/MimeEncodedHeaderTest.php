<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of MimeEncodedHeaderTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(MimeEncodedHeader::class)]
#[CoversClass(AbstractHeader::class)]
#[Group('Headers')]
#[Group('MimeEncodedHeader')]
class MimeEncodedHeaderTest extends TestCase
{
    // @phpstan-ignore-next-line
    protected $consumerService;

    // @phpstan-ignore-next-line
    protected $mpf;

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->onlyMethods([])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->onlyMethods([])
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->onlyMethods([])
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->onlyMethods([])
            ->getMock();
        $this->consumerService = $qscs;
        $this->mpf = $mpf;
    }

    private function newMimeEncodedHeader($name, $value) : MimeEncodedHeader
    {
        return $this->getMockBuilder(MimeEncodedHeader::class)
            ->setConstructorArgs([
                $this->logger,
                $this->mpf,
                $this->consumerService,
                $name,
                $value
            ])
            ->onlyMethods([])
            ->getMockForAbstractClass();
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
