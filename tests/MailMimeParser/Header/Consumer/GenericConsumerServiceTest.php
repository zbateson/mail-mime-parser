<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of GenericConsumerServiceTest
 *
 * @group Consumers
 * @group GenericConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\GenericConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class GenericConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $genericConsumer;

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
        $this->genericConsumer = new GenericConsumerService($pf, $ccs, $qscs);
    }

    public function testConsumeTokens() : void
    {
        $value = "Je\ \t suis\nici";

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Je  suis ici', $ret[0]);
    }
}
