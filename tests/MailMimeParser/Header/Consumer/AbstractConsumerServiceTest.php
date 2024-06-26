<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * Description of AbstractConsumerServiceTest
 *
 * @group Consumers
 * @group AbstractConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class AbstractConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $abstractConsumerStub;

    protected function setUp() : void
    {
        $stub = $this->getMockBuilder('\\' . AbstractConsumerService::class)
            ->setMethods(['processParts', 'isEndToken', 'getPartForToken', 'getTokenSeparators', 'getSubConsumers'])
            ->setConstructorArgs([
                \mmpGetTestLogger(),
                $this->getMockBuilder(HeaderPartFactory::class)->disableOriginalConstructor()->getMock(),
                []
            ])
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
            ->with($value)
            ->willReturn($this->getMockForAbstractClass(IHeaderPart::class));
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

    public function testLiteralTokens() : void
    {
        $value = "Je\ suis\\\nici\\\r\noui";
        $mock = $this->getMockBuilder(Token::class)->disableOriginalConstructor();
        $args = ['Je', ' ', 'suis', "\n", 'ici', "\r\n", 'oui'];
        $parts = [
            $mock->getMock(),
            $mock->getMock(),
            $mock->getMock(),
            $mock->getMock(),
            $mock->getMock(),
            $mock->getMock(),
            $mock->getMock()
        ];

        $stub = $this->abstractConsumerStub;

        $stub->expects($this->exactly(7))
            ->method('getPartForToken')
            ->withConsecutive(
                [$args[0], false],
                [$args[1], true],
                [$args[2], false],
                [$args[3], true],
                [$args[4], false],
                [$args[5], true],
                [$args[6], false]
            )
            ->will($this->onConsecutiveCalls($parts[0], $parts[1], $parts[2], $parts[3], $parts[4], $parts[5], $parts[6]));
        $stub->method('processParts')
            ->willReturn($parts);

        $ret = $stub($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(7, $ret);
    }

    public function testInvokeWithEmptyValue() : void
    {
        $stub = $this->abstractConsumerStub;
        $ret = $stub('');
        $this->assertEmpty($ret);
        $this->assertEquals([], $ret);
    }
}
