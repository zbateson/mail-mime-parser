<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of GenericConsumerMimeLiteralPartServiceTest
 *
 * @group Consumers
 * @group GenericConsumerMimeLiteralPartService
 * @covers ZBateson\MailMimeParser\Header\Consumer\GenericConsumerMimeLiteralPartService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class GenericConsumerMimeLiteralPartServiceTest extends TestCase
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
        $this->genericConsumer = new GenericConsumerMimeLiteralPartService($mpf, $ccs, $qscs);
    }

    public function testConsumeTokens() : void
    {
        $value = "Je\ \t suis\nici";

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Je  suis ici', $ret[0]);
    }

    public function testFilterSpacesBetweenMimeParts() : void
    {
        $value = "=?US-ASCII?Q?Je?=    =?US-ASCII?Q?suis?=\n=?US-ASCII?Q?ici?=";

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Jesuisici', $ret[0]);
    }
}
