<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

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
    private $abstractConsumerStub;

    protected function setUp() : void
    {
        $stub = $this->getMockBuilder('\\' . \ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer::class)
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

    public function testSingleToken()
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

    public function testMultipleTokens()
    {
        $value = "Je\ \t suis\nici";
        $parts = ['Je', ' ', "\t ", 'suis', "\n", 'ici'];

        $stub = $this->abstractConsumerStub;

        $stub->expects($this->exactly(6))
            ->method('getPartForToken')
            ->withConsecutive([$parts[0]], [$parts[1]], [$parts[2]], [$parts[3]], [$parts[4]], [$parts[5]])
            ->will($this->onConsecutiveCalls($parts[0], $parts[1], $parts[2], $parts[3], $parts[4], $parts[5]));
        $stub->method('processParts')
            ->willReturn($parts);

        $ret = $stub($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(6, $ret);
    }

    public function testInvokeWithEmptyValue()
    {
        $stub = $this->abstractConsumerStub;
        $ret = $stub('');
        $this->assertEmpty($ret);
        $this->assertEquals([], $ret);
    }
}
