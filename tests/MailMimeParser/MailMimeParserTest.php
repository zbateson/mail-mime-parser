<?php

namespace ZBateson\MailMimeParser;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Parser\MessageParserService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of MailMimeParserTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(MailMimeParser::class)]
#[Group('MailMimeParser')]
#[Group('Base')]
class MailMimeParserTest extends TestCase
{
    public function testParseFromHandle() : void
    {
        $handle = \fopen('php://memory', 'r+');
        \fwrite($handle, 'This is a test');
        \rewind($handle);

        $exp = $this->createMock(IMessage::class);
        $mockParser = $this->getMockBuilder(MessageParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn($exp);

        $mmp = new MailMimeParser(phpDiContainerConfig: [MessageParserService::class => $mockParser]);

        $ret = $mmp->parse($handle, true);
        $this->assertEquals($exp, $ret);
    }

    public function testParseFromStream() : void
    {
        $handle = \fopen('php://memory', 'r+');
        \fwrite($handle, 'This is a test');
        \rewind($handle);

        $exp = $this->createMock(IMessage::class);
        $mockParser = $this->getMockBuilder(MessageParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn($exp);

        $mmp = new MailMimeParser(phpDiContainerConfig: [MessageParserService::class => $mockParser]);

        $ret = $mmp->parse(Psr7\Utils::streamFor($handle), true);
        $this->assertEquals($exp, $ret);
    }

    public function testParseFromString() : void
    {
        $exp = $this->createMock(IMessage::class);
        $mockParser = $this->getMockBuilder(MessageParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn($exp);

        $mmp = new MailMimeParser(phpDiContainerConfig: [MessageParserService::class => $mockParser]);

        $ret = $mmp->parse('This is a test', false);
        $this->assertEquals($exp, $ret);
    }
}
