<?php
namespace ZBateson\MailMimeParser\Parser\Proxy;

use PHPUnit\Framework\TestCase;

/**
 * ParserMessageProxyTest
 *
 * @group Parser
 * @group ParserMessageProxy
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserMessageProxy
 * @author Zaahid Bateson
 */
class ParserMessageProxyTest extends TestCase
{
    private $partBuilder;
    private $parser;

    protected function setUp() : void
    {
        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\IParser')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testSetGetLastLineEndingLength()
    {
        $instance = new ParserMessageProxy(
            $this->partBuilder,
            $this->parser
        );

        $instance->setLastLineEndingLength(42);
        $this->assertSame(42, $instance->getLastLineEndingLength());
    }
}
