<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * ParserMessageProxyTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(ParserMessageProxy::class)]
#[Group('Parser')]
#[Group('ParserMessageProxy')]
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
            ->getMock();
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
