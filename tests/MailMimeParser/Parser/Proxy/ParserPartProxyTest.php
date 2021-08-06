<?php
namespace ZBateson\MailMimeParser\Parser\Proxy;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7\Utils;

/**
 * ParserPartProxyTest
 *
 * @group Parser
 * @group ParserPartProxy
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserPartProxy
 * @author Zaahid Bateson
 */
class ParserPartProxyTest extends TestCase
{
    private $partBuilder;
    private $childParser;
    private $parent;

    protected function legacySetUp()
    {
        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->childParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\IParser')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->parent = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSetGetPart()
    {
        $part = $this->getMockBuilder('ZBateson\MailMimeParser\Message\IMessagePart')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $instance = new ParserPartProxy($this->partBuilder);
        $instance->setPart($part);
        $this->assertSame($part, $instance->getPart());
    }

    
}
