<?php
namespace ZBateson\MailMimeParser\Parser;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

/**
 * MessageParserTest
 *
 * @group MessageParser
 * @group Parser
 * @covers ZBateson\MailMimeParser\Message\MessageParser
 * @author Zaahid Bateson
 */
class MessageParserTest extends TestCase
{
    private $instance;
    private $partBuilderFactory;
    private $partHeaderContainerFactory;
    private $parserManager;
    private $headerParser;

    protected function legacySetUp()
    {
        $this->partBuilderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partHeaderContainerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->parserManager = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\ParserManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\HeaderParser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new MessageParser(
            $this->partBuilderFactory,
            $this->partHeaderContainerFactory,
            $this->parserManager,
            $this->headerParser
        );
    }

    public function testParse()
    {
        $stream = Psr7\Utils::streamFor('test');
        $msg = $this->getMockForAbstractClass('ZBateson\MailMimeParser\IMessage');

        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $hc = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $proxy = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $this->partHeaderContainerFactory
            ->expects($this->once())
            ->method('newInstance')
            ->willReturn($hc);
        $this->partBuilderFactory
            ->expects($this->once())
            ->method('newPartBuilder')
            ->with($hc, $stream)
            ->willReturn($pb);
        $pb->expects($this->once())
            ->method('getMessageResourceHandle')
            ->willReturn('test');
        $this->headerParser
            ->expects($this->once())
            ->method('parse')
            ->with('test', $hc);
        $this->parserManager
            ->expects($this->once())
            ->method('createParserProxyFor')
            ->with($pb)
            ->willReturn($proxy);
        $proxy->expects($this->once())
            ->method('getPart')
            ->willReturn($msg);

        $this->assertSame($msg, $this->instance->parse($stream));
        $stream->close();
    }

    public function testReadLine()
    {
        $stream = Psr7\Utils::streamFor(
            "This is a string\n"
            . "with multiple lines,\n"
            . "multiple lines..."
        );
        $handle = Psr7\StreamWrapper::getResource($stream);
        $this->assertEquals("This is a string\n", MessageParser::readLine($handle));
        $this->assertEquals("with multiple lines,\n", MessageParser::readLine($handle));
        $this->assertEquals("multiple lines...", MessageParser::readLine($handle));
        $this->assertFalse(MessageParser::readLine($handle));
        $stream->close();
    }

    public function testReadLineWith4096Chars()
    {
        $checkDiscarded = str_repeat('a', 4096);
        $checkLarger = $checkDiscarded . $checkDiscarded;
        $stream = Psr7\Utils::streamFor(
            $checkDiscarded . "\n"
            . $checkLarger . "\n"
            . 'last line'
        );
        $handle = Psr7\StreamWrapper::getResource($stream);
        $this->assertEquals(substr($checkDiscarded, 0, -1), MessageParser::readLine($handle));
        $this->assertEquals(substr($checkDiscarded, 0, -1), MessageParser::readLine($handle));
        $this->assertEquals('last line', MessageParser::readLine($handle));
        $this->assertFalse(MessageParser::readLine($handle));
        $stream->close();
    }
}
