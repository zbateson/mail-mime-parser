<?php

namespace ZBateson\MailMimeParser;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * Description of MailMimeParserTest
 *
 * @group MailMimeParser
 * @group Base
 * @covers ZBateson\MailMimeParser\MailMimeParser
 * @author Zaahid Bateson
 */
class MailMimeParserTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mockDi;

    public function testParseFromHandle() : void
    {
        $handle = \fopen('php://memory', 'r+');
        \fwrite($handle, 'This is a test');
        \rewind($handle);

        $mockParser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\MessageParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn('test');

        $mmp = new MailMimeParser(null, $mockParser);

        $ret = $mmp->parse($handle, true);
        $this->assertEquals('test', $ret);
    }

    public function testParseFromStream() : void
    {
        $handle = \fopen('php://memory', 'r+');
        \fwrite($handle, 'This is a test');
        \rewind($handle);

        $mockParser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\MessageParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn('test');

        $mmp = new MailMimeParser(null, $mockParser);

        $ret = $mmp->parse(Psr7\Utils::streamFor($handle), true);
        $this->assertEquals('test', $ret);
    }

    public function testParseFromString() : void
    {
        $mockParser = $this->getMockBuilder(\ZBateson\MailMimeParser\Parser\MessageParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn('test');

        $mmp = new MailMimeParser(null, $mockParser);

        $ret = $mmp->parse('This is a test', false);
        $this->assertEquals('test', $ret);
    }
}
