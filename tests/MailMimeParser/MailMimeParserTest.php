<?php

namespace ZBateson\MailMimeParser;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Parser\MessageParserService;

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
    public function getServiceInterface($mockParser)
    {
        return new class($mockParser) implements \Pimple\ServiceProviderInterface {
            private $parser;
            function __construct($parser) {
                $this->parser = $parser;
            }
            function register(\Pimple\Container $pimple) {
                $pimple[MessageParserService::class] = $this->parser;
            }
        };
    }

    public function testParseFromHandle() : void
    {
        $handle = \fopen('php://memory', 'r+');
        \fwrite($handle, 'This is a test');
        \rewind($handle);

        $exp = $this->getMockForAbstractClass(IMessage::class);
        $mockParser = $this->getMockBuilder(MessageParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn($exp);

        $mmp = new MailMimeParser([ $this->getServiceInterface($mockParser) ]);

        $ret = $mmp->parse($handle, true);
        $this->assertEquals($exp, $ret);
    }

    public function testParseFromStream() : void
    {
        $handle = \fopen('php://memory', 'r+');
        \fwrite($handle, 'This is a test');
        \rewind($handle);

        $exp = $this->getMockForAbstractClass(IMessage::class);
        $mockParser = $this->getMockBuilder(MessageParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn($exp);

        $mmp = new MailMimeParser([ $this->getServiceInterface($mockParser) ]);

        $ret = $mmp->parse(Psr7\Utils::streamFor($handle), true);
        $this->assertEquals($exp, $ret);
    }

    public function testParseFromString() : void
    {
        $exp = $this->getMockForAbstractClass(IMessage::class);
        $mockParser = $this->getMockBuilder(MessageParserService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn($exp);

        $mmp = new MailMimeParser([ $this->getServiceInterface($mockParser) ]);

        $ret = $mmp->parse('This is a test', false);
        $this->assertEquals($exp, $ret);
    }
}
