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
    private $partHeaderContainerFactory;
    private $streamFactory;
    private $baseParser;
    private $parsedMessageFactory;

    protected function legacySetUp()
    {
        $this->partHeaderContainerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->streamFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->baseParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\BaseParser')
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new PartBuilderFactory(
            $this->partHeaderContainerFactory,
            $this->streamFactory,
            $this->baseParser
        );

        $this->parsedMessageFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParsedMessageFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testNewPartBuilder()
    {
        $phc = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partHeaderContainerFactory
            ->expects($this->exactly(2))
            ->method('newInstance')
            ->willReturn($phc);

        $stream = Psr7\stream_for('test');
        $partBuilder = $this->instance->newPartBuilder(
            $this->parsedMessageFactory,
            $stream
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\PartBuilder',
            $partBuilder
        );

        $childPartBuilder = $this->instance->newChildPartBuilder(
            $this->parsedMessageFactory,
            $partBuilder
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\PartBuilder',
            $childPartBuilder
        );
        $this->assertSame($partBuilder, $childPartBuilder->getParent());
        $stream->close();
    }
}
