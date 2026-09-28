<?php

namespace ZBateson\MailMimeParser\Parser;

use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Message\HeaderBudget;

/**
 * HeaderParserServiceTest
 *
 * @group HeaderParserService
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\HeaderParserService
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

    public function testParseStopsAtMaxHeaderCount() : void
    {
        $instance = new HeaderParserService(3);
        $headers = '';
        for ($i = 0; $i < 20; $i++) {
            $headers .= "X-H$i: v\r\n";
        }
        $res = StreamWrapper::getResource(Utils::streamFor($headers . "\r\nbody"));
        $this->headerContainer->expects($this->exactly(3))->method('add');
        $this->headerContainer->expects($this->once())->method('addError');
        $instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseStopsAtMaxHeaderSizeBytes() : void
    {
        $instance = new HeaderParserService(1000, 20);
        $headers = '';
        for ($i = 0; $i < 20; $i++) {
            $headers .= "X-Header-$i: value\r\n";
        }
        $res = StreamWrapper::getResource(Utils::streamFor($headers . "\r\nbody"));
        $this->headerContainer->expects($this->once())->method('addError');
        $instance->parse($res, $this->headerContainer);
        \fclose($res);
    }

    public function testParseReadsPastTheHeadersAfterReachingALimit() : void
    {
        $instance = new HeaderParserService(maxHeaderCount: 3);
        $headers = '';
        for ($i = 0; $i < 20; $i++) {
            $headers .= "X-H$i: v\r\n";
        }
        $res = StreamWrapper::getResource(Utils::streamFor($headers . "\r\nbody"));
        $instance->parse($res, $this->headerContainer);
        $this->assertSame('body', \fread($res, 100));
        \fclose($res);
    }

    public function testParseStopsAtMessageHeaderCount() : void
    {
        $budget = new HeaderBudget(100, 2, 1000);
        $this->headerContainer->method('getBudget')->willReturn($budget);
        $res = StreamWrapper::getResource(Utils::streamFor("A: 1\r\nB: 2\r\nC: 3\r\nD: 4\r\n\r\nbody"));
        $this->headerContainer->expects($this->exactly(2))->method('add');
        $this->headerContainer->expects($this->once())
            ->method('addError')
            ->with($this->stringContains('Message header count limit of 2 reached'));
        $this->instance->parse($res, $this->headerContainer);
        $this->assertSame(0, $budget->getRemainingHeaders());
        $this->assertSame('body', \fread($res, 100));
        \fclose($res);
    }

    public function testParseStopsAtMessageHeaderSizeBytes() : void
    {
        $budget = new HeaderBudget(100, 100, 12);
        $this->headerContainer->method('getBudget')->willReturn($budget);
        $res = StreamWrapper::getResource(Utils::streamFor("A: 1\r\nB: 2\r\nC: 3\r\nD: 4\r\n\r\nbody"));
        $this->headerContainer->expects($this->once())
            ->method('addError')
            ->with($this->stringContains('Message header size limit of 12 bytes reached'));
        $this->instance->parse($res, $this->headerContainer);
        $this->assertSame(0, $budget->getRemainingBytes());
        $this->assertSame('body', \fread($res, 100));
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
