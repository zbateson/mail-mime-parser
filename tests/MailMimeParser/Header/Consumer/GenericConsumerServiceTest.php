<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of GenericConsumerServiceTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(GenericConsumerService::class)]
#[CoversClass(AbstractConsumerService::class)]
#[CoversClass(AbstractGenericConsumerService::class)]
#[Group('Consumers')]
#[Group('GenericConsumerService')]
class GenericConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $genericConsumer;

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
        $this->genericConsumer = new GenericConsumerService($this->logger, $pf, $ccs, $qscs);
    }

    public function testConsumeTokens() : void
    {
        $value = "Je\ \t suis\n ici";

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Je  suis ici', $ret[0]->getValue());
    }
}
