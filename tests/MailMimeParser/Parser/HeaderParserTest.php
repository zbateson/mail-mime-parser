<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

/**
 * HeaderParserTest
 *
 * @group HeaderParser
 * @group Parser
 * @covers ZBateson\MailMimeParser\Message\HeaderParser
 * @author Zaahid Bateson
 */
class HeaderParserTest extends TestCase
{
    private $headerContainer;

    private $instance;

    protected function setUp() : void
    {
        $this->headerContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new HeaderParser();
    }

    public function testParseEmptyStreamDoesNothing()
    {
        $res = StreamWrapper::getResource(Utils::streamFor(''));
        $this->headerContainer->expects($this->never())->method('add');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleLine()
    {
        $res = StreamWrapper::getResource(Utils::streamFor('The-Header: The Value'));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', 'The Value');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleLineWithFollowingEmptyLine()
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The Value\r\n\r\n"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', 'The Value');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleLineWithMultipleColons()
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The: Value\r\n"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', 'The: Value');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleLineWithNoColons()
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header The Value\r\n"));
        $this->headerContainer->expects($this->never())->method('add');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseValidAndInvalidLines()
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header The Value\r\nAnother-Header: An actual value\r\n\r\n"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('Another-Header', 'An actual value');
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleMultilineHeaderWithSpaceSeparator()
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The\r\n Value"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', "The\r\n Value");
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleMultilineHeaderWithMultiSpaceSeparators()
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The\r\n   Value"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', "The\r\n   Value");
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleMultilineHeaderWithTabSeparator()
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The\r\n\tValue"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', "The\r\n\tValue");
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleMultilineHeaderWithMultiTabSeparators()
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The\r\n\t\t\tValue"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', "The\r\n\t\t\tValue");
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseSingleMultilineHeaderWithMixedSeparators()
    {
        $res = StreamWrapper::getResource(Utils::streamFor("The-Header: The\r\n\t \tValue"));
        $this->headerContainer->expects($this->once())
            ->method('add')
            ->with('The-Header', "The\r\n\t \tValue");
        $this->instance->parse($res, $this->headerContainer);
        \fclose($res);
    }
}
