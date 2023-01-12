<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use PHPUnit\Framework\TestCase;

/**
 * ParserPartChildrenContainerFactoryTest
 *
 * @group ParserPartChildrenContainerFactory
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainerFactory
 * @author Zaahid Bateson
 */
class ParserPartChildrenContainerFactoryTest extends TestCase
{
    private $instance;

    private $proxy;

    protected function setUp() : void
    {
        $this->proxy = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy')
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new ParserPartChildrenContainerFactory();
    }

    public function testNewInstance()
    {
        $ob = $this->instance->newInstance($this->proxy);
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainer',
            $ob
        );
        // make sure proxy is attached
        $this->proxy->expects($this->once())
            ->method('popNextChild')
            ->willReturn(null);
        $ob->offsetExists(0);
    }
}
