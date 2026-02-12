<?php

namespace ZBateson\MailMimeParser\Parser\Proxy;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * ParserNonMimeMessageProxyTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(ParserNonMimeMessageProxy::class)]
#[Group('Parser')]
#[Group('ParserNonMimeMessageProxy')]
class ParserNonMimeMessageProxyTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $partBuilder;

    // @phpstan-ignore-next-line
    private $parser;

    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $this->partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\IParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new ParserNonMimeMessageProxy(
            $this->partBuilder,
            $this->parser
        );
    }

    public function testSetGetNextPartStart() : void
    {
        $this->assertNull($this->instance->getNextPartStart());
        $this->instance->setNextPartStart(42);
        $this->assertSame(42, $this->instance->getNextPartStart());
    }

    public function testSetGetNextPartMode() : void
    {
        $this->assertNull($this->instance->getNextPartMode());
        $this->instance->setNextPartMode(42);
        $this->assertSame(42, $this->instance->getNextPartMode());
    }

    public function testSetGetNextPartFilename() : void
    {
        $this->assertNull($this->instance->getNextPartFilename());
        $this->instance->setNextPartFilename('booya');
        $this->assertSame('booya', $this->instance->getNextPartFilename());
    }

    public function testClearNextPart() : void
    {
        $this->instance->setNextPartStart(42);
        $this->instance->setNextPartMode(42);
        $this->instance->setNextPartFilename('booya');
        $this->instance->clearNextPart();
        $this->assertNull($this->instance->getNextPartStart());
        $this->assertNull($this->instance->getNextPartMode());
        $this->assertNull($this->instance->getNextPartFilename());
    }
}
