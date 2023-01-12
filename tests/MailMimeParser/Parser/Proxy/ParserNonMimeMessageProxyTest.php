<?php
namespace ZBateson\MailMimeParser\Parser\Proxy;

use PHPUnit\Framework\TestCase;

/**
 * ParserNonMimeMessageProxyTest
 *
 * @group Parser
 * @group ParserNonMimeMessageProxy
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserNonMimeMessageProxy
 * @author Zaahid Bateson
 */
class ParserNonMimeMessageProxyTest extends TestCase
{
    private $partBuilder;
    private $parser;
    private $instance;

    protected function setUp() : void
    {
        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\IParser')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->instance = new ParserNonMimeMessageProxy(
            $this->partBuilder,
            $this->parser
        );
    }

    public function testSetGetNextPartStart()
    {
        $this->assertNull($this->instance->getNextPartStart());
        $this->instance->setNextPartStart(42);
        $this->assertSame(42, $this->instance->getNextPartStart());
    }

    public function testSetGetNextPartMode()
    {
        $this->assertNull($this->instance->getNextPartMode());
        $this->instance->setNextPartMode(42);
        $this->assertSame(42, $this->instance->getNextPartMode());
    }

    public function testSetGetNextPartFilename()
    {
        $this->assertNull($this->instance->getNextPartFilename());
        $this->instance->setNextPartFilename('booya');
        $this->assertSame('booya', $this->instance->getNextPartFilename());
    }

    public function testClearNextPart()
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
