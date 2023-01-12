<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

/**
 * NonMimeParserTest
 *
 * @group NonMimeParser
 * @group Parser
 * @covers ZBateson\MailMimeParser\Message\AbstractParser
 * @covers ZBateson\MailMimeParser\Message\NonMimeParser
 * @author Zaahid Bateson
 */
class NonMimeParserTest extends TestCase
{
    private $messageProxyFactory;

    private $partProxyFactory;

    private $partBuilderFactory;

    private $headerContainerFactory;

    private $headerParser;

    private $parserManager;

    private $partBuilder;

    private $parserMessageProxy;

    private $headerContainer;

    private $instance;

    protected function setUp() : void
    {
        $this->messageProxyFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserNonMimeMessageProxyFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->partProxyFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserUUEncodedPartProxyFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->partBuilderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilderFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->headerContainerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\UUEncodedPartHeaderContainerFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->headerParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\HeaderParser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parserManager = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\ParserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new NonMimeParser(
            $this->messageProxyFactory,
            $this->partProxyFactory,
            $this->partBuilderFactory,
            $this->headerContainerFactory
        );
        $this->instance->setParserManager($this->parserManager);

        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parserMessageProxy = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserNonMimeMessageProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $this->headerContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\UUEncodedPartHeaderContainer')
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
        $this->assertTrue($this->instance->canParse($this->partBuilder));
    }

    public function testParseEmptyContent()
    {
        $handle = StreamWrapper::getResource(Utils::streamFor(''));
        $this->parserMessageProxy->expects($this->atLeastOnce())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);
        $this->parserMessageProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(0);
        $this->parserMessageProxy->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with(0);

        $this->instance->parseContent($this->parserMessageProxy);
        // on feof(), returns early
        $this->instance->parseContent($this->parserMessageProxy);
        \fclose($handle);
    }

    public function testParseContentWithNonNullNextPartStart()
    {
        $handle = StreamWrapper::getResource(Utils::streamFor('test'));
        $this->parserMessageProxy->expects($this->atLeastOnce())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);
        $this->parserMessageProxy->expects($this->once())
            ->method('getNextPartStart')
            ->willReturn(1);
        $this->parserMessageProxy->expects($this->never())
            ->method('setStreamContentStartPos');
        $this->parserMessageProxy->expects($this->never())
            ->method('setStreamPartAndContentEndPos');

        $this->instance->parseContent($this->parserMessageProxy);
        \fclose($handle);
    }

    public function testParseContentReadsLinesToEnd()
    {
        $str = "test\r\ntoost\r\ntoast";
        $handle = StreamWrapper::getResource(Utils::streamFor($str));
        $this->parserMessageProxy->expects($this->atLeastOnce())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);
        $this->parserMessageProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(0);
        $this->parserMessageProxy->expects($this->exactly(3))
            ->method('setStreamPartAndContentEndPos')
            ->withConsecutive([$this->anything()], [$this->anything()], [\strlen($str)]);

        $this->instance->parseContent($this->parserMessageProxy);
        \fclose($handle);
    }

    public function testParseContentReadsLinesToUUEncodeBeginLine()
    {
        $first = "test\r\ntoost\r\n";
        $begin = "begin 714 test\r\n";
        $end = 'blah';
        $str = $first . $begin . $end;

        $handle = StreamWrapper::getResource(Utils::streamFor($str));
        $this->parserMessageProxy->expects($this->atLeastOnce())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);
        $this->parserMessageProxy->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(0);
        $this->parserMessageProxy->expects($this->exactly(2))
            ->method('setStreamPartAndContentEndPos')
            ->withConsecutive([$this->anything()], [\strlen($first)]);

        $this->parserMessageProxy->expects($this->once())
            ->method('setNextPartStart')
            ->with(\strlen($first));
        $this->parserMessageProxy->expects($this->once())
            ->method('setNextPartMode')
            ->with(714);
        $this->parserMessageProxy->expects($this->once())
            ->method('setNextPartFilename')
            ->with('test');

        $this->instance->parseContent($this->parserMessageProxy);
        \fclose($handle);
    }

    public function testParseNextChild()
    {
        $handle = StreamWrapper::getResource(Utils::streamFor('test'));

        $this->parserMessageProxy->expects($this->atLeastOnce())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);
        $this->parserMessageProxy->expects($this->atLeastOnce())
            ->method('getNextPartStart')
            ->willReturn(42);
        $this->parserMessageProxy->expects($this->atLeastOnce())
            ->method('getNextPartMode')
            ->willReturn(666);
        $this->parserMessageProxy->expects($this->atLeastOnce())
            ->method('getNextPartFilename')
            ->willReturn('v0ol');

        $this->headerContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with(666, 'v0ol')
            ->willReturn($this->headerContainer);

        $this->partBuilderFactory->expects($this->once())
            ->method('newChildPartBuilder')
            ->with($this->headerContainer, $this->parserMessageProxy)
            ->willReturn($this->partBuilder);

        $this->parserManager->expects($this->once())
            ->method('createParserProxyFor')
            ->with($this->partBuilder)
            ->willReturn('A little somefin');

        $this->partBuilder->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with(42);
        $this->partBuilder->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with(42);

        $this->parserMessageProxy->expects($this->once())
            ->method('clearNextPart');

        $this->assertSame('A little somefin', $this->instance->parseNextChild($this->parserMessageProxy));
    }

    public function testParseNextChildWithNullNextPartStartOrHandleAtEof()
    {
        $handle = StreamWrapper::getResource(Utils::streamFor('test'));
        \stream_get_contents($handle);

        $this->parserMessageProxy->expects($this->atLeastOnce())
            ->method('getMessageResourceHandle')
            ->willReturn($handle);
        $this->parserMessageProxy->expects($this->exactly(2))
            ->method('getNextPartStart')
            ->willReturnOnConsecutiveCalls(null, 1);

        $this->assertNull($this->instance->parseNextChild($this->parserMessageProxy));
        $this->assertNull($this->instance->parseNextChild($this->parserMessageProxy));
    }
}
