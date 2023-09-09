<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * Description of AbstractConsumerTest
 *
 * @group Consumers
 * @group AbstractConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class AbstractConsumerTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $abstractConsumerStub;

    protected function setUp() : void
    {
        $stub = $this->getMockBuilder('\\' . AbstractConsumer::class)
            ->setMethods(['processParts', 'isEndToken', 'getPartForToken', 'getTokenSeparators', 'getSubConsumers'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $stub->method('isEndToken')
            ->willReturn(false);
        $stub->method('getTokenSeparators')
            ->willReturn(['\s+']);
        $stub->method('getSubConsumers')
            ->willReturn([]);

        $this->abstractConsumerStub = $stub;
    }

    public function testSingleToken() : void
    {
        $value = 'teapot';
        $stub = $this->abstractConsumerStub;

        $stub->expects($this->once())
            ->method('getPartForToken')
            ->with($value);
        $stub->method('processParts')
            ->willReturn([$value]);

        $ret = $stub($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
    }

    public function testMultipleTokens() : void
    {
        $value = "Je\ \t suis\nici";
        $mock = $this->getMockBuilder(Token::class)->disableOriginalConstructor();
        $args = ['Je', ' ', "\t ", 'suis', "\n", 'ici'];
        $parts = [
            $mock->getMock(),
            $mock->getMock(),
            $mock->getMock(),
            $mock->getMock(),
            $mock->getMock(),
            $mock->getMock()
        ];

        $stub = $this->abstractConsumerStub;

        $stub->expects($this->exactly(6))
            ->method('getPartForToken')
            ->withConsecutive([$args[0]], [$args[1]], [$args[2]], [$args[3]], [$args[4]], [$args[5]])
            ->will($this->onConsecutiveCalls($parts[0], $parts[1], $parts[2], $parts[3], $parts[4], $parts[5]));
        $stub->method('processParts')
            ->willReturn($parts);

        $ret = $stub($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(6, $ret);
    }

    public function testInvokeWithEmptyValue() : void
    {
        $stub = $this->abstractConsumerStub;
        $ret = $stub('');
        $this->assertEmpty($ret);
        $this->assertEquals([], $ret);
    }
}
