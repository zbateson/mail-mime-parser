<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

/**
 * MimeParserTest
 *
 * @group MimeParser
 * @group Parser
 * @covers ZBateson\MailMimeParser\Message\AbstractParser
 * @covers ZBateson\MailMimeParser\Message\MimeParser
 * @author Zaahid Bateson
 */
class MimeParserTest extends TestCase
{
    private $messageProxyFactory;

    private $partProxyFactory;

    private $partBuilderFactory;

    private $headerContainerFactory;

    private $headerParser;

    private $parserManager;

    private $partBuilder;

    private $parserPartProxy;

    private $headerContainer;

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

        $this->headerParser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\HeaderParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->parserManager = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\ParserManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new MimeParser(
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

    public function testAbstractParserGetters()
    {
        $this->assertSame($this->messageProxyFactory, $this->instance->getParserMessageProxyFactory());
        $this->assertSame($this->partProxyFactory, $this->instance->getParserPartProxyFactory());
    }

    public function testCanParse()
    {
        $this->partBuilder->expects($this->exactly(2))
            ->method('isMime')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($this->instance->canParse($this->partBuilder));
        $this->assertFalse($this->instance->canParse($this->partBuilder));
    }

    public function testParseEmptyContent()
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

    public function testParseContentLinesWithoutBoundary()
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
            ->withConsecutive([2], [2], [2], [0]);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(\strlen($str));
        $this->parserPartProxy->expects($this->once())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseContentLinesWithLeadingDashesWithoutBoundary()
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
            ->withConsecutive([2], [2], [2], [0]);
        $this->parserPartProxy->expects($this->exactly(4))
            ->method('setEndBoundaryFound')
            ->withConsecutive(['--Some'], ['--Lines'], ['--Of'], ['--Text'])
            ->willReturn(false);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(\strlen($str));
        $this->parserPartProxy->expects($this->once())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseContentLinesWithBoundary()
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
            ->withConsecutive([2], [2], [2], [0]);
        $this->parserPartProxy->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->withConsecutive(['--Of'], ['--Text'])
            ->willReturnOnConsecutiveCalls(false, true);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(\strlen($str) - \strlen('--Text') - 2);
        $this->parserPartProxy->expects($this->never())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseContentLinesWithBoundarySetsCorrectLastLineEndingLength()
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
            ->withConsecutive([2], [2], [1], [0]);
        $this->parserPartProxy->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->withConsecutive(['--Of'], ['--Text'])
            ->willReturnOnConsecutiveCalls(false, true);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(\strlen($str) - \strlen('--Text') - 1);
        $this->parserPartProxy->expects($this->never())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseContentLinesIgnoresLongBoundaryLine()
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
            ->withConsecutive([2], [2], [2], [0]);
        $this->parserPartProxy->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->withConsecutive(['--Of'], [$boundary])
            ->willReturn(false);
        $this->parserPartProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(\strlen($str));
        $this->parserPartProxy->expects($this->once())
            ->method('setEof');

        $this->instance->parseContent($this->parserPartProxy);
        \fclose($handle);
    }

    public function testParseNextChild()
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
            ->willReturn('groot');

        $this->assertSame('groot', $this->instance->parseNextChild($this->parserPartProxy));
    }

    public function testParseNextChildWhenParentBoundaryFoundReturnsNull()
    {
        $this->parserPartProxy->expects($this->once())
            ->method('isParentBoundaryFound')
            ->willReturn(true);
        $this->assertNull($this->instance->parseNextChild($this->parserPartProxy));
    }

    public function testParseNextChildAfterParentEndBoundaryFound()
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
