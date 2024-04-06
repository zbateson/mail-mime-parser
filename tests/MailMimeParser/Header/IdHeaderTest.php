<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\IdConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;

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
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$mpf, $qscs])
            ->setMethods(['__toString'])
            ->getMock();
        $idcs = $this->getMockBuilder(IdConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, $qscs])
            ->setMethods(['__toString'])
            ->getMock();
        $this->consumerService = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\IdBaseConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, $qscs, $idcs])
            ->setMethods(['__toString'])
            ->getMock();
        $this->mimeTokenPartFactory = $mpf;
    }

    public function testGetId() : void
    {
        $header = new IdHeader($this->mimeTokenPartFactory, $this->consumerService, 'Content-ID', ' <1337@example.com> ');
        $this->assertEquals('1337@example.com', $header->getValue());
    }

    public function testGetIdWithInvalidId() : void
    {
        $header = new IdHeader($this->mimeTokenPartFactory, $this->consumerService, 'Content-ID', 'Test');
        $this->assertEquals('Test', $header->getValue());
    }

    public function testGetIdWithEmptyValue() : void
    {
        $header = new IdHeader($this->mimeTokenPartFactory, $this->consumerService, 'Content-ID', '');
        $this->assertNull($header->getValue());
        $this->assertEquals([], $header->getIds());
    }

    public function testGetIds() : void
    {
        $header = new IdHeader($this->mimeTokenPartFactory, $this->consumerService, 'References', ' <1337@example.com> <7331@example.com> <4@example.com> ');
        $this->assertEquals('1337@example.com', $header->getValue());
        $this->assertEquals(['1337@example.com', '7331@example.com', '4@example.com'], $header->getIds());
    }

    public function testGetIdsWithComments() : void
    {
        $header = new IdHeader($this->mimeTokenPartFactory, $this->consumerService, 'References', '(blah)<1337@example(test).com>(wha<asdf>t!)<"7331"@example.com><4(test)@example.com> ');
        $this->assertEquals('1337@example.com', $header->getValue());
        $this->assertEquals(['1337@example.com', '7331@example.com', '4@example.com'], $header->getIds());
    }

    public function testGetIdsWithInvalidValue() : void
    {
        $header = new IdHeader($this->mimeTokenPartFactory, $this->consumerService, 'In-Reply-To', 'Blah Blah');
        $this->assertEquals('Blah', $header->getValue());
        $this->assertEquals(['Blah', 'Blah'], $header->getIds());
    }

    public function testGetIdsWithMimeLiteralParts() : void
    {
        $header = new IdHeader(
            $this->mimeTokenPartFactory,
            $this->consumerService,
            'References',
            '=?us-ascii?Q?<CACrVqsLQjPe0y=3DE4q0auFowDoY+9Z27R63OA=5F1fn-?= '
            . '=?us-ascii?Q?mGPG9Zc3Q@example.com>_<a1527a80a42422457ebe?= '
            . '=?us-ascii?Q?89657a5d0e89@example.com>?='
        );
        $this->assertEquals(
            'CACrVqsLQjPe0y=E4q0auFowDoY+9Z27R63OA_1fn-mGPG9Zc3Q@example.com',
            $header->getValue()
        );
        $this->assertEquals(
            [
                'CACrVqsLQjPe0y=E4q0auFowDoY+9Z27R63OA_1fn-mGPG9Zc3Q@example.com',
                'a1527a80a42422457ebe89657a5d0e89@example.com'
            ],
            $header->getIds()
        );
    }

    public function testReferencesHeader() : void
    {
        $header = new IdHeader(
            $this->mimeTokenPartFactory,
            $this->consumerService,
            'References',
            '=?us-ascii?Q?' . "\r\n"
            . '<86c6f658-a49a-709a-5089-75c73560128b@local.test>_'
            . '<7afxgxia=5F2336078@local.test>_<504FD9' . "\r\n"
            . 'B2-E05?= =?us-ascii?Q?6-4971-A722-953664BEFB5F@local.test>?=' . "\r\n"
            . '<2786730_7afxgxia@local.test>'
        );
        $this->assertEquals(
            '86c6f658-a49a-709a-5089-75c73560128b@local.test',
            $header->getValue()
        );
        $this->assertEquals(
            [
                '86c6f658-a49a-709a-5089-75c73560128b@local.test',
                '7afxgxia_2336078@local.test',
                '504FD9B2-E056-4971-A722-953664BEFB5F@local.test',
                '2786730_7afxgxia@local.test'
            ],
            $header->getIds()
        );
    }
}
