<?php
namespace ZBateson\MailMimeParser\Parser\Part;

use LegacyPHPUnit\TestCase;

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
    
    protected function legacySetUp()
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
            ->method('parseNextChild')
            ->willReturn(null);
        $ob->offsetExists(0);
    }
}
