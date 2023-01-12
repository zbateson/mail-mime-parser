<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of QuotedStringConsumerTest
 *
 * @group Consumers
 * @group QuotedStringConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class QuotedStringConsumerTest extends TestCase
{
    private $quotedStringConsumer;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder('ZBateson\MbWrapper\MbWrapper')
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\HeaderPartFactory')
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mlpf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory')
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $cs = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Consumer\ConsumerService')
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
        $this->quotedStringConsumer = new QuotedStringConsumer($cs, $pf);
    }

    public function testConsumeTokens()
    {
        $value = 'Will end at " quote';

        $ret = $this->quotedStringConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Will end at ', $ret[0]);
    }
}
