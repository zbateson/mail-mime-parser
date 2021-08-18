<?php
namespace ZBateson\MailMimeParser\Parser;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;

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
        $hc = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $stream = Psr7\stream_for('test');
        $partBuilder = $this->instance->newPartBuilder(
            $hc,
            $stream
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\PartBuilder',
            $partBuilder
        );

        $parent = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy')
            ->disableOriginalConstructor()
            ->setMethods([ 'getMessageResourceHandle' ])
            ->getMockForAbstractClass();
        $parent->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn(StreamWrapper::getResource($stream));

        $childPartBuilder = $this->instance->newChildPartBuilder(
            $hc,
            $parent
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\PartBuilder',
            $childPartBuilder
        );
        $this->assertSame(0, $childPartBuilder->getStreamPartStartPos());
        $stream->close();
    }
}
