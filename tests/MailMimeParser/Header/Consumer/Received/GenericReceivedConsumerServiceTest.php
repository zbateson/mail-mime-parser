<?php

namespace ZBateson\MailMimeParser\Header\Consumer\Received;

use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;

/**
 * Description of GenericReceivedConsumerServiceTest
 *
 * @group Consumers
 * @group GenericReceivedConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumerService
 * @author Zaahid Bateson
 */
class GenericReceivedConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $genericConsumer;
    private $logger;

    protected function setUp() : void
    {
        $this->logger = new NullLogger();
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
        $this->genericConsumer = new GenericReceivedConsumerService($this->logger, $pf, $ccs, 'test');
    }

    public function testConsumeTokens() : void
    {
        $value = "Je \t suis\nici";

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Je suis ici', $ret[0]);
    }

    public function testEndsAtViaWithIdAndFor() : void
    {
        $tests = [
            'sweet via sugar',
            'sweet with honey',
            'sweet id 1',
            'sweet for you'
        ];
        foreach ($tests as $t) {
            $ret = $this->genericConsumer->__invoke($t);
            $this->assertNotEmpty($ret);
            $this->assertCount(1, $ret);
            $this->assertEquals('sweet', $ret[0]);
        }
    }

    public function testWithSingleComments() : void
    {
        $str = 'sweet (via sugar) bee';
        $ret = $this->genericConsumer->__invoke($str);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('sweet bee', $ret[0]);
        $this->assertEquals('via sugar', $ret[1]->getComment());
    }

    public function testWithMultipleComments() : void
    {
        $str = 'sweet (as can) (surely) bee (innit)';
        $ret = $this->genericConsumer->__invoke($str);
        $this->assertNotEmpty($ret);
        $this->assertCount(4, $ret);
        $this->assertEquals('sweet bee', $ret[0]);
        $this->assertEquals('as can', $ret[1]->getComment());
        $this->assertEquals('surely', $ret[2]->getComment());
        $this->assertEquals('innit', $ret[3]->getComment());
    }

    public function testWithSeparatorInWords() : void
    {
        $str = 'bullets within abe and stuff';
        $ret = $this->genericConsumer->__invoke($str);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('bullets within abe and stuff', $ret[0]);
    }
}
