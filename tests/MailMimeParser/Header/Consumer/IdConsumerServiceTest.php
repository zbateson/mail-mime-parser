<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use Psr\Log\NullLogger;
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
        $this->idConsumer = new IdConsumerService($this->logger, $pf, $ccs, $qscs);
    }

    public function testConsumeId() : void
    {
        $ret = $this->idConsumer->__invoke('id123@host.name>');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ContainerPart::class, $address);
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
        $this->assertCount(1, $ret);
        $comments = $ret[0]->getComments();
        $this->assertNotEmpty($comments);
        $this->assertCount(1, $comments);

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ContainerPart::class, $ret[0]);
        $this->assertEquals('firstquoted', $ret[0]->getValue());
        $this->assertEquals('comment', $comments[0]->getComment());
    }
}
