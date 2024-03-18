<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * MessageParserServiceTest
 *
 * @group MessageParserService
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\MessageParserService
 * @author Zaahid Bateson
 */
class MessageParserServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $instance;

    // @phpstan-ignore-next-line
    private $partBuilderFactory;

    // @phpstan-ignore-next-line
    private $partHeaderContainerFactory;

    // @phpstan-ignore-next-line
    private $parserManager;

    // @phpstan-ignore-next-line
    private $headerParser;

    protected function setUp() : void
    {
        $this->partBuilderFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partHeaderContainerFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parserManager = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\ParserManagerService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerParser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\HeaderParserService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new MessageParserService(
            $this->partBuilderFactory,
            $this->partHeaderContainerFactory,
            $this->parserManager,
            $this->headerParser
        );
    }

    public function testParse() : void
    {
        $stream = Psr7\Utils::streamFor('test');
        $msg = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\IMessage::class);

        $pb = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $hc = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $proxy = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->partHeaderContainerFactory
            ->expects($this->once())
            ->method('newInstance')
            ->willReturn($hc);
        $this->partBuilderFactory
            ->expects($this->once())
            ->method('newPartBuilder')
            ->with($hc, $stream)
            ->willReturn($pb);
        $pb->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn('test');
        $this->headerParser
            ->expects($this->once())
            ->method('parse')
            ->with('test', $hc);
        $this->parserManager
            ->expects($this->once())
            ->method('createParserProxyFor')
            ->with($pb)
            ->willReturn($proxy);
        $proxy->expects($this->once())
            ->method('getPart')
            ->willReturn($msg);

        $this->assertSame($msg, $this->instance->parse($stream));
        $stream->close();
    }

    public function testReadLine() : void
    {
        $stream = Psr7\Utils::streamFor(
            "This is a string\n"
            . "with multiple lines,\n"
            . 'multiple lines...'
        );
        $handle = Psr7\StreamWrapper::getResource($stream);
        $this->assertEquals("This is a string\n", MessageParserService::readLine($handle));
        $this->assertEquals("with multiple lines,\n", MessageParserService::readLine($handle));
        $this->assertEquals('multiple lines...', MessageParserService::readLine($handle));
        $this->assertFalse(MessageParserService::readLine($handle));
        $stream->close();
    }

    public function testReadLineWith4096Chars() : void
    {
        $checkDiscarded = \str_repeat('a', 4096);
        $checkLarger = $checkDiscarded . $checkDiscarded;
        $stream = Psr7\Utils::streamFor(
            $checkDiscarded . "\n"
            . $checkLarger . "\n"
            . 'last line'
        );
        $handle = Psr7\StreamWrapper::getResource($stream);
        $this->assertEquals(\substr($checkDiscarded, 0, -1), MessageParserService::readLine($handle));
        $this->assertEquals(\substr($checkDiscarded, 0, -1), MessageParserService::readLine($handle));
        $this->assertEquals('last line', MessageParserService::readLine($handle));
        $this->assertFalse(MessageParserService::readLine($handle));
        $stream->close();
    }
}
