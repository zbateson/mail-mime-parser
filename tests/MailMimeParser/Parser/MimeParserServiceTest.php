<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\ConsecutiveCallsTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * MimeParserServiceTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(AbstractParserService::class)]
#[CoversClass(MimeParserService::class)]
#[Group('MimeParserService')]
#[Group('Parser')]
class MimeParserServiceTest extends TestCase
{
    use ConsecutiveCallsTrait;
    // @phpstan-ignore-next-line
    private $messageProxyFactory;

    // @phpstan-ignore-next-line
    private $partProxyFactory;

    // @phpstan-ignore-next-line
    private $partBuilderFactory;

    // @phpstan-ignore-next-line
    private $headerContainerFactory;

    // @phpstan-ignore-next-line
    private $headerParser;

    // @phpstan-ignore-next-line
    private $parserManager;

    // @phpstan-ignore-next-line
    private $partBuilder;

    // @phpstan-ignore-next-line
    private $parserPartProxy;

    // @phpstan-ignore-next-line
    private $headerContainer;

    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $this->messageProxyFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMessageProxyFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->partProxyFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxyFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->partBuilderFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->headerContainerFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->headerParser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\HeaderParserService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->parserManager = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\ParserManagerService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new MimeParserService(
            $this->messageProxyFactory,
            $this->partProxyFactory,
            $this->partBuilderFactory,
            $this->headerContainerFactory,
            $this->headerParser
        );
        $this->instance->setParserManager($this->parserManager);

        $this->partBuilder = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\PartBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->parserPartProxy = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->headerContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAbstractParserGetters() : void
    {
        $this->assertSame($this->messageProxyFactory, $this->instance->getParserMessageProxyFactory());
        $this->assertSame($this->partProxyFactory, $this->instance->getParserPartProxyFactory());
    }

    public function testCanParse() : void
    {
        $this->partBuilder->expects($this->exactly(2))
            ->method('isMime')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($this->instance->canParse($this->partBuilder));
        $this->assertFalse($this->instance->canParse($this->partBuilder));
    }

    public function testParseEmptyContent() : void
    {
        $handle = StreamWrapper::getResource(Utils::streamFor(''));
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandlePos')
            ->willReturn(42);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(42);
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(0);
        $this->parserPartProxy->expects($this->once())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseContentLinesWithoutBoundary() : void
    {
        $str = "Some\r\nLines\r\nOf\r\nText";
        $handle = StreamWrapper::getResource(Utils::streamFor($str));
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandlePos')
            ->willReturn(42);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(42);
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);

        $this->parserPartProxy->expects($this->exactly(4))
            ->method('getLastLineEndingLength')
            ->willReturnOnConsecutiveCalls(0, 2, 2, 2);
        $this->parserPartProxy->expects($this->exactly(4))
            ->method('setLastLineEndingLength')
            ->with(...$this->consecutive([2], [2], [2], [0]));
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(\strlen($str));
        $this->parserPartProxy->expects($this->once())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseContentLinesWithLeadingDashesWithoutBoundary() : void
    {
        $str = "--Some\r\n--Lines\r\n--Of\r\n--Text";
        $handle = StreamWrapper::getResource(Utils::streamFor($str));
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandlePos')
            ->willReturn(42);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(42);
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);

        $this->parserPartProxy->expects($this->exactly(4))
            ->method('getLastLineEndingLength')
            ->willReturnOnConsecutiveCalls(0, 2, 2, 2);
        $this->parserPartProxy->expects($this->exactly(4))
            ->method('setLastLineEndingLength')
            ->with(...$this->consecutive([2], [2], [2], [0]));
        $this->parserPartProxy->expects($this->exactly(4))
            ->method('setEndBoundaryFound')
            ->with(...$this->consecutive(['--Some'], ['--Lines'], ['--Of'], ['--Text']))
            ->willReturn(false);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(\strlen($str));
        $this->parserPartProxy->expects($this->once())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseContentLinesWithBoundary() : void
    {
        $str = "Some\r\nLines\r\n--Of\r\n--Text";
        $handle = StreamWrapper::getResource(Utils::streamFor($str));
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandlePos')
            ->willReturn(42);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(42);
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);

        $this->parserPartProxy->expects($this->exactly(4))
            ->method('getLastLineEndingLength')
            ->willReturnOnConsecutiveCalls(0, 2, 2, 2);
        $this->parserPartProxy->expects($this->exactly(4))
            ->method('setLastLineEndingLength')
            ->with(...$this->consecutive([2], [2], [2], [0]));
        $this->parserPartProxy->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->with(...$this->consecutive(['--Of'], ['--Text']))
            ->willReturnOnConsecutiveCalls(false, true);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(\strlen($str) - \strlen('--Text') - 2);
        $this->parserPartProxy->expects($this->never())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseEmptyContentWithBoundaryAndStartingLineEndingLength() : void
    {
        $str = '--boundary';
        $handle = StreamWrapper::getResource(Utils::streamFor($str));
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandlePos')
            ->willReturn(42);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(42);
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);

        $this->parserPartProxy->expects($this->exactly(1))
            ->method('getLastLineEndingLength')
            ->willReturnOnConsecutiveCalls(2);
        $this->parserPartProxy->expects($this->exactly(1))
            ->method('setLastLineEndingLength')
            ->with(...$this->consecutive([0]));
        $this->parserPartProxy->expects($this->exactly(1))
            ->method('setEndBoundaryFound')
            ->with(...$this->consecutive(['--boundary']))
            ->willReturnOnConsecutiveCalls(true);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(-2);
        $this->parserPartProxy->expects($this->never())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseContentLinesWithBoundarySetsCorrectLastLineEndingLength() : void
    {
        $str = "Some\r\nLines\r\n--Of\n--Text";
        $handle = StreamWrapper::getResource(Utils::streamFor($str));
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandlePos')
            ->willReturn(42);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(42);
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);

        $this->parserPartProxy->expects($this->exactly(4))
            ->method('getLastLineEndingLength')
            ->willReturnOnConsecutiveCalls(0, 2, 2, 1);
        $this->parserPartProxy->expects($this->exactly(4))
            ->method('setLastLineEndingLength')
            ->with(...$this->consecutive([2], [2], [1], [0]));
        $this->parserPartProxy->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->with(...$this->consecutive(['--Of'], ['--Text']))
            ->willReturnOnConsecutiveCalls(false, true);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(\strlen($str) - \strlen('--Text') - 1);
        $this->parserPartProxy->expects($this->never())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseContentLinesIgnoresLongBoundaryLine() : void
    {
        // 2044 + '--' + potential \r\n for 2048 limit
        $boundary = '--' . \str_repeat('t', 2044);
        $boundaryLong = '--' . \str_repeat('t', 2045);

        $str = "Some\r\n--Of\r\n$boundaryLong\r\n$boundary";
        $handle = StreamWrapper::getResource(Utils::streamFor($str));
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandlePos')
            ->willReturn(42);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(42);
        $this->parserPartProxy->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);

        $this->parserPartProxy->expects($this->exactly(4))
            ->method('getLastLineEndingLength')
            ->willReturnOnConsecutiveCalls(0, 2, 2, 2);
        $this->parserPartProxy->expects($this->exactly(4))
            ->method('setLastLineEndingLength')
            ->with(...$this->consecutive([2], [2], [2], [0]));
        $this->parserPartProxy->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->with(...$this->consecutive(['--Of'], [$boundary]))
            ->willReturn(false);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(\strlen($str));
        $this->parserPartProxy->expects($this->once())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseNextChild() : void
    {
        $this->parserPartProxy->expects($this->once())
            ->method('isParentBoundaryFound')
            ->willReturn(false);
        $this->headerContainerFactory->expects($this->once())
            ->method('newInstance')
            ->willReturn($this->headerContainer);
        $this->partBuilderFactory->expects($this->once())
            ->method('newChildPartBuilder')
            ->with($this->headerContainer, $this->parserPartProxy)
            ->willReturn($this->partBuilder);
        $this->parserPartProxy->expects($this->once())
            ->method('isEndBoundaryFound')
            ->willReturn(false);

        $this->partBuilder->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn('test');
        $this->headerParser->expects($this->once())
            ->method('parse')
            ->with('test', $this->headerContainer);
        $this->parserManager->expects($this->once())
            ->method('createParserProxyFor')
            ->with($this->partBuilder)
            ->willReturn($this->parserPartProxy);

        $this->assertSame($this->parserPartProxy, $this->instance->parseNextChild($this->parserPartProxy));
    }

    public function testParseNextChildWhenParentBoundaryFoundReturnsNull() : void
    {
        $this->parserPartProxy->expects($this->once())
            ->method('isParentBoundaryFound')
            ->willReturn(true);
        $this->assertNull($this->instance->parseNextChild($this->parserPartProxy));
    }

    public function testParseNextChildAfterParentEndBoundaryFound() : void
    {
        $this->parserPartProxy->expects($this->once())
            ->method('isParentBoundaryFound')
            ->willReturn(false);
        $this->headerContainerFactory->expects($this->once())
            ->method('newInstance')
            ->willReturn($this->headerContainer);
        $this->partBuilderFactory->expects($this->once())
            ->method('newChildPartBuilder')
            ->with($this->headerContainer, $this->parserPartProxy)
            ->willReturn($this->partBuilder);
        $this->parserPartProxy->expects($this->once())
            ->method('isEndBoundaryFound')
            ->willReturn(true);

        $endPartProxy = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->partProxyFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->partBuilder, $this->instance)
            ->willReturn($endPartProxy);

        // reads content
        $handle = StreamWrapper::getResource(Utils::streamFor('asdf'));
        $endPartProxy->expects($this->once())
            ->method('getMessageResourceHandlePos')
            ->willReturn(42);
        $endPartProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(42);
        $endPartProxy->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);
        $endPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(4);
        $endPartProxy->expects($this->once())
            ->method('setEof');

        $this->assertNull($this->instance->parseNextChild($this->parserPartProxy));
    }
}
