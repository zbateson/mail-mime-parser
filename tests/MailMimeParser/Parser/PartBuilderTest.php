<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;
use PHPUnit\Framework\TestCase;

/**
 * PartBuilderTest
 *
 * @group PartBuilder
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\PartBuilder
 * @author Zaahid Bateson
 */
class PartBuilderTest extends TestCase
{
  // @phpstan-ignore-next-line
    private $headerContainer;

    protected function setUp() : void
    {
        $this->headerContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newPartBuilder($stream = null, $parent = null) : PartBuilder
    {
        if ($stream === null && $parent === null) {
            $stream = Psr7\Utils::streamFor('test');
        } elseif ($parent !== null) {
            $stream = null;
        }
        return new PartBuilder(
            $this->headerContainer,
            $stream,
            $parent
        );
    }

    public function testGetHeaderContainer() : void
    {
        $instance = $this->newPartBuilder();
        $this->assertSame($this->headerContainer, $instance->getHeaderContainer());
    }

    public function testSetStreamPartPosAndGetFilename() : void
    {
        $instance = $this->newPartBuilder();
        $instance->setStreamPartStartPos(42);
        $instance->setStreamPartEndPos(84);
        $this->assertEquals(42, $instance->getStreamPartStartPos());
        $this->assertEquals(42, $instance->getStreamPartLength());
    }

    public function testSetStreamContentPosAndGetFilename() : void
    {
        $instance = $this->newPartBuilder();
        $instance->setStreamPartStartPos(11);
        $instance->setStreamContentStartPos(42);
        $instance->setStreamPartAndContentEndPos(84);
        $this->assertEquals(11, $instance->getStreamPartStartPos());
        $this->assertEquals(84 - 11, $instance->getStreamPartLength());
        $this->assertEquals(42, $instance->getStreamContentStartPos());
        $this->assertEquals(84 - 42, $instance->getStreamContentLength());
    }

    public function testSetStreamContentPosAndGetFilenameWithParent() : void
    {
        $parent = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMessageResourceHandle', 'setStreamPartEndPos'])
            ->getMockForAbstractClass();

        $stream = Psr7\Utils::streamFor('test');
        $parent->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn(StreamWrapper::getResource($stream));
        $parent->expects($this->once())
            ->method('setStreamPartEndPos')
            ->with(84);

        $instance = $this->newPartBuilder(null, $parent);

        $instance->setStreamPartStartPos(22);
        $instance->setStreamContentStartPos(42);
        $instance->setStreamPartAndContentEndPos(84);

        $this->assertEquals(42, $instance->getStreamContentStartPos());
        $this->assertEquals(84 - 42, $instance->getStreamContentLength());
        $this->assertEquals(22, $instance->getStreamPartStartPos());
        $this->assertEquals(84 - 22, $instance->getStreamPartLength());
    }
}
