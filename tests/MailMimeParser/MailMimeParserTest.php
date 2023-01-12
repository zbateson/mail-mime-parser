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
    private $mockDi;

    protected function setUp() : void
    {
        $this->mockDi = $this->getMockBuilder('ZBateson\MailMimeParser\Container')
            ->disableOriginalConstructor()
            ->setMethods(['offsetGet', 'offsetExists'])
            ->getMock();
    }

    protected function legacyTearDown()
    {
        MailMimeParser::setDependencyContainer(null);
    }

    public function testConstructMailMimeParser()
    {
        MailMimeParser::setDependencyContainer($this->mockDi);
        $mmp = new MailMimeParser();
        $this->assertNotNull($mmp);
    }

    public function testParseFromHandle()
    {
        $handle = \fopen('php://memory', 'r+');
        \fwrite($handle, 'This is a test');
        \rewind($handle);

        $mockParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\MessageParser')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDi
            ->expects($this->once())
            ->method('offsetGet')
            ->with('ZBateson\MailMimeParser\Parser\MessageParser')
            ->willReturn($mockParser);
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn('test');

        MailMimeParser::setDependencyContainer($this->mockDi);
        $mmp = new MailMimeParser();

        $ret = $mmp->parse($handle, true);
        $this->assertEquals('test', $ret);
    }

    public function testParseFromStream()
    {
        $handle = \fopen('php://memory', 'r+');
        \fwrite($handle, 'This is a test');
        \rewind($handle);

        $mockParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\MessageParser')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDi
            ->expects($this->once())
            ->method('offsetGet')
            ->with('ZBateson\MailMimeParser\Parser\MessageParser')
            ->willReturn($mockParser);
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn('test');

        MailMimeParser::setDependencyContainer($this->mockDi);
        $mmp = new MailMimeParser();

        $ret = $mmp->parse(Psr7\Utils::streamFor($handle), true);
        $this->assertEquals('test', $ret);
    }

    public function testParseFromString()
    {
        $mockParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\MessageParser')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDi
            ->expects($this->once())
            ->method('offsetGet')
            ->with('ZBateson\MailMimeParser\Parser\MessageParser')
            ->willReturn($mockParser);
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn('test');

        MailMimeParser::setDependencyContainer($this->mockDi);
        $mmp = new MailMimeParser();

        $ret = $mmp->parse('This is a test', false);
        $this->assertEquals('test', $ret);
    }
}
