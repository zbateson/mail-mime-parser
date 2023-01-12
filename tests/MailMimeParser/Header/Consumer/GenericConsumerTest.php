<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of GenericConsumerTest
 *
 * @group Consumers
 * @group GenericConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\GenericConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class GenericConsumerTest extends TestCase
{
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
        $mlpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $cs = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\ConsumerService::class)
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
        $this->genericConsumer = new GenericConsumer($cs, $mlpf);
    }

    public function testConsumeTokens()
    {
        $value = "Je\ \t suis\nici";

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Je  suis ici', $ret[0]);
    }

    public function testFilterSpacesBetweenMimeParts()
    {
        $value = "=?US-ASCII?Q?Je?=    =?US-ASCII?Q?suis?=\n=?US-ASCII?Q?ici?=";

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Jesuisici', $ret[0]);
    }
}
