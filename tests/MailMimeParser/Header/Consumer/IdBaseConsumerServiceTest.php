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

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods()
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->setMethods()
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $qscs])
            ->setMethods()
            ->getMock();
        $idcs = $this->getMockBuilder(IdConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, $qscs])
            ->setMethods()
            ->getMock();
        $this->idBaseConsumer = new IdBaseConsumerService($this->logger, $pf, $ccs, $qscs, $idcs);
    }

    public function testConsumeId() : void
    {
        $ret = $this->idBaseConsumer->__invoke('<id123@host.name>');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ContainerPart::class, $address);
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

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ContainerPart::class, $ret[1]);
        $this->assertEquals('first-id', $ret[1]->getValue());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\CommentPart::class, $ret[2]);
        $this->assertEquals('comment', $ret[2]->getComment());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ContainerPart::class, $ret[3]);
        $this->assertEquals('second-id@asdf', $ret[3]->getValue());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ContainerPart::class, $ret[4]);
        $this->assertEquals('third-id', $ret[4]->getValue());
    }
}
