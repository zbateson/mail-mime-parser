<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of QuotedStringConsumerServiceTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(QuotedStringConsumerService::class)]
#[CoversClass(AbstractConsumerService::class)]
#[Group('Consumers')]
#[Group('QuotedStringConsumerService')]
class QuotedStringConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $quotedStringConsumer;

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
        $this->quotedStringConsumer = new QuotedStringConsumerService($this->logger, $pf);
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
