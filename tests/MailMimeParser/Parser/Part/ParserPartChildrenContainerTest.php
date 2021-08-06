<?php
namespace ZBateson\MailMimeParser\Parser\Part;

use LegacyPHPUnit\TestCase;

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
    private $instance;
    private $proxy;

    protected function legacySetUp()
    {
        $this->proxy = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy')
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new ParserPartChildrenContainer($this->proxy);
    }

    public function testOffsetExistsCallsProxyOnceAfterReturningNull()
    {
        $this->proxy->expects($this->once())
            ->method('parseNextChild')
            ->willReturn(null);
        $this->assertFalse($this->instance->offsetExists(0));
        // doesn't call parseNextChildAgain
        $this->assertFalse($this->instance->offsetExists(0));
    }

    public function testOffsetExistsCallsProxyTwiceAfterNotReturningNull()
    {
        $this->proxy->expects($this->exactly(2))
            ->method('parseNextChild')
            ->willReturnOnConsecutiveCalls('test', null);
        $this->assertFalse($this->instance->offsetExists(0));
        $this->assertFalse($this->instance->offsetExists(0));
        // doesn't call parseNextChildAgain
        $this->assertFalse($this->instance->offsetExists(0));
    }
}
