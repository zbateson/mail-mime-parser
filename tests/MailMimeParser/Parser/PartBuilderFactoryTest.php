<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * PartBuilderFactoryTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(PartBuilderFactory::class)]
#[Group('PartBuilderFactory')]
#[Group('Parser')]
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
            ->onlyMethods(['getMessageResourceHandle'])
            ->getMock();
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
