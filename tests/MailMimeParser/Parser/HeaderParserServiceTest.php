<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

/**
 * HeaderParserServiceTest
 *
 * @group HeaderParserService
 * @group Parser
 * @covers ZBateson\MailMimeParser\Message\HeaderParserService
 * @author Zaahid Bateson
 */
class HeaderParserServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $headerContainer;

    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $this->headerContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new HeaderParserService();
    }

    public function testParseEmptyStreamDoesNothing() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor(''));
        $this->headerContainer->expects($this->never())->method('add');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleLine() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor('The-Header: The Value'));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', 'The Value');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleLineWithFollowingEmptyLine() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The Value\r\n\r\n"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', 'The Value');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleLineWithMultipleColons() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The: Value\r\n"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', 'The: Value');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleLineWithNoColons() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header The Value\r\n"));
        $this->headerContainer->expects($this->never())->method('add');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseValidAndInvalidLines() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header The Value\r\nAnother-Header: An actual value\r\n\r\n"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('Another-Header', 'An actual value');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleMultilineHeaderWithSpaceSeparator() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The\r\n Value"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', "The\r\n Value");
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleMultilineHeaderWithMultiSpaceSeparators() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The\r\n   Value"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', "The\r\n   Value");
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleMultilineHeaderWithTabSeparator() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The\r\n\tValue"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', "The\r\n\tValue");
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleMultilineHeaderWithMultiTabSeparators() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The\r\n\t\t\tValue"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', "The\r\n\t\t\tValue");
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleMultilineHeaderWithMixedSeparators() : void
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The\r\n\t \tValue"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', "The\r\n\t \tValue");
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }
}
