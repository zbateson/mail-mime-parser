<?php
namespace ZBateson\MailMimeParser\Parser;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;

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
    private $headerContainer;

    protected function legacySetUp()
    {
        $this->headerContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newPartBuilder($stream = null, $parent = null)
    {
        if ($stream === null && $parent === null) {
            $stream = Psr7\stream_for('test');
        } elseif ($parent !== null) {
            $stream = null;
        }
        return new PartBuilder(
            $this->headerContainer,
            $stream,
            $parent
        );
    }

    public function testGetHeaderContainer()
    {
        $instance = $this->newPartBuilder();
        $this->assertSame($this->headerContainer, $instance->getHeaderContainer());
    }

    public function testSetStreamPartPosAndGetFilename()
    {
        $instance = $this->newPartBuilder();
        $instance->setStreamPartStartPos(42);
        $instance->setStreamPartEndPos(84);
        $this->assertEquals(42, $instance->getStreamPartStartPos());
        $this->assertEquals(42, $instance->getStreamPartLength());
    }

    public function testSetStreamContentPosAndGetFilename()
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

    public function testSetStreamContentPosAndGetFilenameWithParent()
    {
        $parent = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy')
            ->disableOriginalConstructor()
            ->setMethods([ 'getMessageResourceHandle', 'setStreamPartEndPos' ])
            ->getMockForAbstractClass();

        $stream = Psr7\stream_for('test');
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
