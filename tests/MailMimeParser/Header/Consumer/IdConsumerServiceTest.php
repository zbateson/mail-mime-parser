<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of IdConsumerTest
 *
 * @group Consumers
 * @group IdConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\IdConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class IdConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $idConsumer;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MbWrapperService::class)
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
        $cs = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\ConsumerService::class)
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
        $this->idConsumer = new IdConsumerService($cs, $pf);
    }

    public function testIsService() : void
    {
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Container\IService::class, $this->idConsumer);
    }

    public function testConsumeId() : void
    {
        $ret = $this->idConsumer->__invoke('id123@host.name>');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $address);
        $this->assertEquals('id123@host.name', $address->getValue());
    }

    public function testConsumeSpaces() : void
    {
        $ret = $this->idConsumer->__invoke('An id without an end');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $this->assertEquals('Anidwithoutanend', $ret[0]->getValue());
    }

    public function testConsumeIdWithComments() : void
    {
        $ret = $this->idConsumer->__invoke('first (comment) "quoted"');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $ret[0]);
        $this->assertEquals('firstquoted', $ret[0]->getValue());
        $this->assertEquals('comment', $ret[1]->getComment());
    }
}
