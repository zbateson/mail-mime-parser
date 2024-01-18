<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of QuotedStringConsumerServiceTest
 *
 * @group Consumers
 * @group QuotedStringConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class QuotedStringConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $quotedStringConsumer;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $this->quotedStringConsumer = new QuotedStringConsumerService($pf);
    }

    public function testConsumeTokens() : void
    {
        $value = 'Will end at " quote';

        $ret = $this->quotedStringConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Will end at ', $ret[0]);
    }
}
