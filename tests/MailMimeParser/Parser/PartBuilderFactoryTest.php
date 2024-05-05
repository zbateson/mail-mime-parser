<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;
use PHPUnit\Framework\TestCase;

/**
 * PartBuilderFactoryTest
 *
 * @group PartBuilderFactory
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\PartBuilderFactory
 * @author Zaahid Bateson
 */
class PartBuilderFactoryTest extends TestCase
{
  // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $this->instance = new PartBuilderFactory();
    }

    public function testNewPartBuilder() : void
    {
        $hc = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stream = Psr7\Utils::streamFor('test');
        $partBuilder = $this->instance->newPartBuilder(
            $hc,
            $stream
        );
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Parser\PartBuilder::class,
            $partBuilder
        );

        $parent = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMessageResourceHandle'])
            ->getMockForAbstractClass();
        $parent->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn(StreamWrapper::getResource($stream));

        $childPartBuilder = $this->instance->newChildPartBuilder(
            $hc,
            $parent
        );
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Parser\PartBuilder::class,
            $childPartBuilder
        );
        $this->assertSame(0, $childPartBuilder->getStreamPartStartPos());
        $stream->close();
    }
}
