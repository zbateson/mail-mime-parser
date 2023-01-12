<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of IdBaseConsumerTest
 *
 * @group Consumers
 * @group IdBaseConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\IdBaseConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class IdBaseConsumerTest extends TestCase
{
    private $idBaseConsumer;

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
        $cs = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\ConsumerService::class)
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
        $this->idBaseConsumer = new IdBaseConsumer($cs, $pf);
    }

    public function testConsumeId()
    {
        $ret = $this->idBaseConsumer->__invoke('<id123@host.name>');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $address);
        $this->assertEquals('id123@host.name', $address->getValue());
    }

    public function testConsumeIds()
    {
        $ret = $this->idBaseConsumer->__invoke('<first-id> <second-id@asdf> <third-id>');
        $this->assertNotEmpty($ret);
        $this->assertCount(3, $ret);

        $this->assertEquals('first-id', $ret[0]->getValue());
        $this->assertEquals('second-id@asdf', $ret[1]->getValue());
        $this->assertEquals('third-id', $ret[2]->getValue());
    }

    public function testConsumeIdsWithComments()
    {
        $ret = $this->idBaseConsumer->__invoke('(first) <first-id> (comment) <second-id@asdf> <third-id>');
        $this->assertNotEmpty($ret);
        $this->assertCount(3, $ret);

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $ret[0]);
        $this->assertEquals('first-id', $ret[0]->getValue());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $ret[1]);
        $this->assertEquals('second-id@asdf', $ret[1]->getValue());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $ret[2]);
        $this->assertEquals('third-id', $ret[2]->getValue());
    }
}
