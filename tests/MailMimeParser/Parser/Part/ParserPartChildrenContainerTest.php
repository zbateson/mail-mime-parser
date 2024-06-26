<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use PHPUnit\Framework\TestCase;

/**
 * ParserPartChildrenContainerTest
 *
 * @group Parser
 * @group ParserPartChildrenContainer
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainer
 * @author Zaahid Bateson
 */
class ParserPartChildrenContainerTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $instance;

    // @phpstan-ignore-next-line
    private $proxy;

    protected function setUp() : void
    {
        $this->proxy = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new ParserPartChildrenContainer($this->proxy);
    }

    public function testOffsetExistsCallsProxyOnceAfterReturningNull() : void
    {
        $this->proxy->expects($this->once())
            ->method('popNextChild')
            ->willReturn(null);
        $this->assertFalse($this->instance->offsetExists(0));
        // doesn't call popNextChild again
        $this->assertFalse($this->instance->offsetExists(0));
    }

    public function testOffsetExistsCallsProxyTwiceAfterNotReturningNull() : void
    {
        $part = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\IMessagePart::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->proxy->expects($this->exactly(2))
            ->method('popNextChild')
            ->willReturnOnConsecutiveCalls($part, null);
        $this->assertTrue($this->instance->offsetExists(0));
        $this->assertFalse($this->instance->offsetExists(1));
        // doesn't call popNextChild again
        $this->assertFalse($this->instance->offsetExists(1));
    }
}
