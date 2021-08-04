<?php
namespace ZBateson\MailMimeParser\Parser;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

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
    private function newPartBuilder($stream = null, $parent = null)
    {
        if ($stream === null && $parent === null) {
            $stream = Psr7\stream_for('test');
        } elseif ($parent !== null) {
            $stream = null;
        }
        return new PartBuilder(
            $stream,
            $parent
        );
    }

    public function testSetStreamPartPosAndGetFilename()
    {
        $instance = $this->newPartBuilder();
        $instance->setStreamPartStartPos(42);
        $instance->setStreamPartEndPos(84);
        $this->assertEquals(42, $instance->getStreamPartStartOffset());
        $this->assertEquals(42, $instance->getStreamPartLength());
    }

    public function testSetStreamContentPosAndGetFilename()
    {
        $instance = $this->newPartBuilder();
        $instance->setStreamPartStartPos(11);
        $instance->setStreamContentStartPos(42);
        $instance->setStreamPartAndContentEndPos(84);
        $this->assertEquals(11, $instance->getStreamPartStartOffset());
        $this->assertEquals(84 - 11, $instance->getStreamPartLength());
        $this->assertEquals(42, $instance->getStreamContentStartOffset());
        $this->assertEquals(84 - 42, $instance->getStreamContentLength());
    }

    public function testSetStreamContentPosAndGetFilenameWithParent()
    {
        $super = $this->newPartBuilder();
        $parent = $this->newPartBuilder(null, $super);
        $instance = $this->newPartBuilder(null, $parent);

        $super->setStreamPartStartPos(0);
        $super->setStreamContentStartPos(3);
        $super->setStreamPartAndContentEndPos(3);

        $parent->setStreamPartStartPos(11);
        $parent->setStreamContentStartPos(13);
        $parent->setStreamPartAndContentEndPos(20);

        $instance->setStreamPartStartPos(22);
        $instance->setStreamContentStartPos(42);
        $instance->setStreamPartAndContentEndPos(84);

        $this->assertEquals(42, $instance->getStreamContentStartOffset());
        $this->assertEquals(84 - 42, $instance->getStreamContentLength());
        $this->assertEquals(22, $instance->getStreamPartStartOffset());
        $this->assertEquals(84 - 22, $instance->getStreamPartLength());

        $this->assertEquals(13, $parent->getStreamContentStartOffset());
        $this->assertEquals(20 - 13, $parent->getStreamContentLength());
        $this->assertEquals(11, $parent->getStreamPartStartOffset());
        $this->assertEquals(84 - 11, $parent->getStreamPartLength());

        $this->assertEquals(3, $super->getStreamContentStartOffset());
        $this->assertEquals(0, $super->getStreamContentLength());
        $this->assertEquals(0, $super->getStreamPartStartOffset());
        $this->assertEquals(84, $super->getStreamPartLength());
    }
}
