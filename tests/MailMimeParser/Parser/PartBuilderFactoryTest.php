<?php
namespace ZBateson\MailMimeParser\Parser;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

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
    private $instance;
    
    protected function legacySetUp()
    {
        $this->instance = new PartBuilderFactory();
    }

    public function testNewPartBuilder()
    {
        $stream = Psr7\stream_for('test');
        $partBuilder = $this->instance->newPartBuilder(
            $stream
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\PartBuilder',
            $partBuilder
        );

        $childPartBuilder = $this->instance->newChildPartBuilder(
            $partBuilder
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\PartBuilder',
            $childPartBuilder
        );
        $stream->close();
    }
}
