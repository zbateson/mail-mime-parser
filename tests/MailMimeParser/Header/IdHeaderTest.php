<?php
namespace ZBateson\MailMimeParser\Header;

use LegacyPHPUnit\TestCase;

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
    protected $mimeLiteralPartFactory;

    protected function legacySetUp()
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

    public function testGetId()
    {
        $header = new IdHeader($this->mimeLiteralPartFactory, $this->consumerService, 'Content-ID', ' <1337@example.com> ');
        $this->assertEquals('1337@example.com', $header->getValue());
    }

    public function testGetIdWithInvalidId()
    {
        $header = new IdHeader($this->mimeLiteralPartFactory, $this->consumerService, 'Content-ID', 'Test');
        $this->assertEquals('Test', $header->getValue());
    }

    public function testGetIdWithEmptyValue()
    {
        $header = new IdHeader($this->mimeLiteralPartFactory, $this->consumerService, 'Content-ID', '');
        $this->assertNull($header->getValue());
        $this->assertEquals([], $header->getIds());
    }

    public function testGetIds()
    {
        $header = new IdHeader($this->mimeLiteralPartFactory, $this->consumerService, 'References', ' <1337@example.com> <7331@example.com> <4@example.com> ');
        $this->assertEquals('1337@example.com', $header->getValue());
        $this->assertEquals([ '1337@example.com', '7331@example.com', '4@example.com' ], $header->getIds());
    }

    public function testGetIdsWithComments()
    {
        $header = new IdHeader($this->mimeLiteralPartFactory, $this->consumerService, 'References', '(blah)<1337@example(test).com>(wha<asdf>t!)<"7331"@example.com><4(test)@example.com> ');
        $this->assertEquals('1337@example.com', $header->getValue());
        $this->assertEquals([ '1337@example.com', '7331@example.com', '4@example.com' ], $header->getIds());
    }

    public function testGetIdsWithInvalidValue()
    {
        $header = new IdHeader($this->mimeLiteralPartFactory, $this->consumerService, 'In-Reply-To', 'Blah Blah');
        $this->assertEquals('Blah', $header->getValue());
        $this->assertEquals(['Blah', 'Blah'], $header->getIds());
    }

    public function testGetIdsWithMimeLiteralParts()
    {
        $header = new IdHeader(
            $this->mimeLiteralPartFactory,
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
    
    public function testReferencesHeader()
    {
        $header = new IdHeader(
            $this->mimeLiteralPartFactory,
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
