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
    private $parsedMessageFactory;
    private $headerParser;

    protected function legacySetUp()
    {
        $this->partBuilderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->parsedMessageFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParsedMessageFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\HeaderParser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new MessageParser(
            $this->partBuilderFactory,
            $this->parsedMessageFactory,
            $this->headerParser
        );
    }

    public function testParse()
    {
        $stream = Psr7\stream_for('test');
        $msg = $this->getMockForAbstractClass('ZBateson\MailMimeParser\IMessage');
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partBuilderFactory
            ->expects($this->once())
            ->method('newPartBuilder')
            ->with($this->parsedMessageFactory, $stream)
            ->willReturn($pb);
        $this->headerParser
            ->expects($this->once())
            ->method('parse')
            ->with($pb);
        $pb->expects($this->once())
            ->method('createMessagePart')
            ->willReturn($msg);
        $this->assertSame($msg, $this->instance->parse($stream));
        $stream->close();
    }

    public function testReadLine()
    {
        $stream = Psr7\stream_for(
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
        $stream = Psr7\stream_for(
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
