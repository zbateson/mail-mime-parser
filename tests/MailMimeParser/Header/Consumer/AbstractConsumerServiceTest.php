<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\ConsecutiveCallsTrait;
use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\Token;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of AbstractConsumerServiceTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(AbstractConsumerService::class)]
#[Group('Consumers')]
#[Group('AbstractConsumerService')]
class AbstractConsumerServiceTest extends TestCase
{
    use ConsecutiveCallsTrait;

    // @phpstan-ignore-next-line
    private $abstractConsumerStub;

    protected function setUp() : void
    {
        $stub = $this->getMockBuilder('\\' . AbstractConsumerService::class)
            ->onlyMethods(['processParts', 'isEndToken', 'isStartToken', 'getPartForToken', 'getTokenSeparators'])
            ->setConstructorArgs([
                \mmpGetTestLogger(),
                $this->getMockBuilder(HeaderPartFactory::class)->disableOriginalConstructor()->getMock(),
                []
            ])
            ->getMock();

        $stub->method('isEndToken')
            ->willReturn(false);
        $stub->method('getTokenSeparators')
            ->willReturn(['\s+']);
        $this->abstractConsumerStub = $stub;
    }

    public function testSingleToken() : void
    {
        $value = 'teapot';
        $stub = $this->abstractConsumerStub;

        $stub->expects($this->once())
            ->method('getPartForToken')
            ->with($value)
            ->willReturn($this->createMock(IHeaderPart::class));
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
            ->with(...$this->consecutive([$args[0]], [$args[1]], [$args[2]], [$args[3]], [$args[4]], [$args[5]]))
            ->willReturnOnConsecutiveCalls($parts[0], $parts[1], $parts[2], $parts[3], $parts[4], $parts[5]);
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
            ->with(...$this->consecutive(
                [$args[0], false],
                [$args[1], true],
                [$args[2], false],
                [$args[3], true],
                [$args[4], false],
                [$args[5], true],
                [$args[6], false]
            ))
            ->willReturnOnConsecutiveCalls($parts[0], $parts[1], $parts[2], $parts[3], $parts[4], $parts[5], $parts[6]);
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
