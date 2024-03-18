<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use PHPUnit\Framework\TestCase;

/**
 * ParserMessageProxyTest
 *
 * @group Parser
 * @group ParserMessageProxy
 * @covers ZBateson\MailMimeParser\Parser\Proxy\ParserMessageProxy
 * @author Zaahid Bateson
 */
class ParserMessageProxyTest extends TestCase
{
  // @phpstan-ignore-next-line
    private $partBuilder;

    // @phpstan-ignore-next-line
    private $parser;

    protected function setUp() : void
    {
        $this->partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\IParserService::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testSetGetLastLineEndingLength() : void
    {
        $instance = new ParserMessageProxy(
            $this->partBuilder,
            $this->parser
        );

        $instance->setLastLineEndingLength(42);
        $this->assertSame(42, $instance->getLastLineEndingLength());
    }
}
