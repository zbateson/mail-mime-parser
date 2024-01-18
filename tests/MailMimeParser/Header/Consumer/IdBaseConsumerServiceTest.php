<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of IdBaseConsumerServiceTest
 *
 * @group Consumers
 * @group IdBaseConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\IdBaseConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class IdBaseConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
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
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
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
        $this->idBaseConsumer = new IdBaseConsumerService($pf, $ccs, $qscs, $idcs);
    }

    public function testConsumeId() : void
    {
        $ret = $this->idBaseConsumer->__invoke('<id123@host.name>');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $address);
        $this->assertEquals('id123@host.name', $address->getValue());
    }

    public function testConsumeIds() : void
    {
        $ret = $this->idBaseConsumer->__invoke('<first-id> <second-id@asdf> <third-id>');
        $this->assertNotEmpty($ret);
        $this->assertCount(3, $ret);

        $this->assertEquals('first-id', $ret[0]->getValue());
        $this->assertEquals('second-id@asdf', $ret[1]->getValue());
        $this->assertEquals('third-id', $ret[2]->getValue());
    }

    public function testConsumeIdsWithComments() : void
    {
        $ret = $this->idBaseConsumer->__invoke('(first) <first-id> (comment) <second-id@asdf> <third-id>');
        $this->assertNotEmpty($ret);
        $this->assertCount(5, $ret);

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\CommentPart::class, $ret[0]);
        $this->assertEquals('first', $ret[0]->getComment());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $ret[1]);
        $this->assertEquals('first-id', $ret[1]->getValue());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\CommentPart::class, $ret[2]);
        $this->assertEquals('comment', $ret[2]->getComment());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $ret[3]);
        $this->assertEquals('second-id@asdf', $ret[3]->getValue());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\LiteralPart::class, $ret[4]);
        $this->assertEquals('third-id', $ret[4]->getValue());
    }
}
