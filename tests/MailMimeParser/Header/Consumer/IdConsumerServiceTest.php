<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of IdConsumerTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(IdConsumerService::class)]
#[CoversClass(AbstractConsumerService::class)]
#[Group('Consumers')]
#[Group('IdConsumerService')]
class IdConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $idConsumer;

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
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $qscs])
            ->onlyMethods([])
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
