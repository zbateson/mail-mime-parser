<?php
namespace ZBateson\MailMimeParser\Parser\Part;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7\Utils;

/**
 * ParserPartStreamContainerTest
 *
 * @group Parser
 * @group ParserPartStreamContainer
 * @covers ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer
 * @author Zaahid Bateson
 */
class ParserPartStreamContainerTest extends TestCase
{
    private $instance;
    private $streamFactory;
    private $proxy;

    protected function legacySetUp()
    {
        $this->proxy = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy')
            ->disableOriginalConstructor()
            ->setMethods([ 'parseAll', 'parseContent', 'getPartBuilder' ])
            ->getMockForAbstractClass();
        $this->streamFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new ParserPartStreamContainer($this->streamFactory, $this->proxy);
    }

    public function testHasContentRequestsContentStream()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($pb)
            ->willReturn(null);

        $this->assertFalse($this->instance->hasContent());
        // doesn't call parseContent again
        $this->assertFalse($this->instance->hasContent());
    }

    public function testHasContentRequestsContentStreamReturnsTrue()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $stream = Utils::streamFor('Fighting bears');

        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($pb)
            ->willReturn($stream);

        $this->assertTrue($this->instance->hasContent());
        // doesn't call parseContent again
        $this->assertTrue($this->instance->hasContent());
    }

    public function testGetContentRequestsContentStream()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($pb)
            ->willReturn(null);

        $this->assertNull($this->instance->getContentStream('7bit', '', ''));
        // doesn't call parseContent again
        $this->assertNull($this->instance->getContentStream('7bit', '', ''));
    }

    public function testGetContentRequestsContentStreamReturnsStream()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $stream = Utils::streamFor('Fighting bears');

        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($pb)
            ->willReturn($stream);

        $this->assertSame('Fighting bears', $this->instance->getContentStream('7bit', '', '')->getContents());
        // doesn't call parseContent again
        $this->assertSame('Fighting bears', $this->instance->getContentStream('7bit', '', '')->getContents());
    }

    public function testGetBinaryContentRequestsContentStream()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($pb)
            ->willReturn(null);

        $this->assertNull($this->instance->getBinaryContentStream('7bit'));
        // doesn't call parseContent again
        $this->assertNull($this->instance->getBinaryContentStream('7bit'));
    }

    public function testGetBinaryContentRequestsContentStreamReturnsStream()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $stream = Utils::streamFor('Fighting bears');

        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($pb)
            ->willReturn($stream);

        $this->assertSame('Fighting bears', $this->instance->getBinaryContentStream('7bit')->getContents());
        // doesn't call parseContent again
        $this->assertSame('Fighting bears', $this->instance->getBinaryContentStream('7bit')->getContents());
    }

    public function testSetContentStreamRequestsContentStream()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->proxy->expects($this->once())
            ->method('parseContent');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedContentStream')
            ->with($pb)
            ->willReturn(null);

        $this->instance->setContentStream(Utils::streamFor(''));
        // doesn't call parseContent again
        $this->instance->setContentStream(Utils::streamFor(''));
    }

    public function testGetStreamParsesPart()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $stream = Utils::streamFor('Fighting bears');

        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($pb)
            ->willReturn($stream);

        $this->assertSame($stream, $this->instance->getStream());
        // doesn't call parseAll again
        $this->assertSame($stream, $this->instance->getStream());
    }

    public function testGetStreamAfterUpdate()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $parsedStream = Utils::streamFor('Fighting bOars');
        $stream = Utils::streamFor('Fighting bears');

        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($pb)
            ->willReturn($parsedStream);

        $this->instance->setStream($stream);
        $this->assertSame('Fighting bOars', $this->instance->getStream()->getContents());

        $subject = $this->getMockBuilder('SplSubject')
            ->getMockForAbstractClass();
        $this->instance->update($subject);
        // doesn't call parseAll again, returns $stream
        $this->assertSame($stream, $this->instance->getStream());
    }

    public function testDetachedParsedStream()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $parsedStream = Utils::streamFor('Fighting bOars', [ 'metadata' => [ 'mmp-detached-stream' => true ] ]);
        $instance = new ParserPartStreamContainer($this->streamFactory, $this->proxy);

        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($pb)
            ->willReturn($parsedStream);

        $this->assertSame('Fighting bOars', $instance->getStream()->getContents());
        $this->assertTrue($parsedStream->isReadable());
        unset($instance);
        $this->assertFalse($parsedStream->isReadable());
    }

    public function testAttachedParsedStream()
    {
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $parsedStream = Utils::streamFor('Fighting bOars', [ 'metadata' => [ 'mmp-detached-stream' => false ] ]);
        $instance = new ParserPartStreamContainer($this->streamFactory, $this->proxy);

        $this->proxy->expects($this->once())
            ->method('parseAll');
        $this->proxy->expects($this->once())
            ->method('getPartBuilder')
            ->willReturn($pb);
        $this->streamFactory->expects($this->once())
            ->method('getLimitedPartStream')
            ->with($pb)
            ->willReturn($parsedStream);

        $this->assertSame('Fighting bOars', $instance->getStream()->getContents());
        $this->assertTrue($parsedStream->isReadable());
        unset($instance);
        $this->assertTrue($parsedStream->isReadable());
    }
}
