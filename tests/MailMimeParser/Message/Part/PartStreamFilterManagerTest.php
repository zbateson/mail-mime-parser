<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Psr7;

/**
 * PartStreamFilterManagerTest
 * 
 * @group PartStreamFilterManager
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager
 * @author Zaahid Bateson
 */
class PartStreamFilterManagerTest extends PHPUnit_Framework_TestCase
{
    private $partStreamFilterManager = null;
    private $mockStreamDecoratorFactory = null;
    
    protected function setUp()
    {
        $mocksdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamDecoratorFactory')
            ->getMock();
        $this->partStreamFilterManager = new PartStreamFilterManager($mocksdf);
        $this->mockStreamDecoratorFactory = $mocksdf;
    }
    
    public function testAttachQuotedPrintableDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamDecoratorFactory->expects($this->exactly(1))
            ->method('newQuotedPrintableStreamDecorator')
            ->with($stream)
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);
        $this->assertSame($stream, $this->partStreamFilterManager->getContentStream('quoted-printable', null, null));
    }
    
    public function testAttachBase64Decoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamDecoratorFactory->expects($this->exactly(1))
            ->method('newBase64StreamDecorator')
            ->with($stream)
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);
        $this->assertSame($stream, $this->partStreamFilterManager->getContentStream('base64', null, null));
    }
    
    public function testAttachUUEncodeDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamDecoratorFactory->expects($this->exactly(1))
            ->method('newUUStreamDecorator')
            ->with($stream)
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);
        $this->assertSame($stream, $this->partStreamFilterManager->getContentStream('x-uuencode', null, null));
    }
    
    public function testAttachCharsetConversionDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamDecoratorFactory->expects($this->exactly(1))
            ->method('newCharsetStreamDecorator')
            ->with($stream, 'US-ASCII', 'UTF-8')
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);
        $this->assertSame($stream, $this->partStreamFilterManager->getContentStream(null, 'US-ASCII', 'UTF-8'));
    }
    
    public function testReAttachTransferEncodingDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamDecoratorFactory->expects($this->exactly(1))
            ->method('newQuotedPrintableStreamDecorator')
            ->with($stream)
            ->willReturn($stream);
        $stream->rewind();
        
        $stream2 = Psr7\stream_for('test2');
        $stream3 = Psr7\stream_for('test3');
        $this->mockStreamDecoratorFactory->expects($this->exactly(2))
            ->method('newUUStreamDecorator')
            ->with($stream)
            ->willReturnOnConsecutiveCalls($stream2, $stream3);
        $this->partStreamFilterManager->setStream($stream);

        $manager = $this->partStreamFilterManager;
        $this->assertSame($stream2, $manager->getContentStream('x-uuencode', null, null));
        $this->assertSame($stream2, $manager->getContentStream('x-uuencode', null, null));
        $this->assertSame($stream2, $manager->getContentStream('x-uuencode', null, null));

        $this->assertSame($stream, $manager->getContentStream('quoted-printable', null, null));
        $this->assertSame($stream, $manager->getContentStream('quoted-printable', null, null));

        $this->assertSame($stream3, $manager->getContentStream('x-uuencode', null, null));
    }
    
    public function testReAttachCharsetConversionDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamDecoratorFactory->expects($this->exactly(4))
            ->method('newCharsetStreamDecorator')
            ->withConsecutive(
                [$stream, 'US-ASCII', 'UTF-8'],
                [$stream, 'US-ASCII', 'WINDOWS-1252'],
                [$stream, 'ISO-8859-1', 'WINDOWS-1252'],
                [$stream, 'WINDOWS-1252', 'UTF-8']
            )
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);

        $manager = $this->partStreamFilterManager;
        $this->assertSame($stream, $manager->getContentStream(null, 'US-ASCII', 'UTF-8'));
        $this->assertSame($stream, $manager->getContentStream(null, 'US-ASCII', 'UTF-8'));
        $this->assertSame($stream, $manager->getContentStream(null, 'US-ASCII', 'WINDOWS-1252'));
        $this->assertSame($stream, $manager->getContentStream(null, 'ISO-8859-1', 'WINDOWS-1252'));
        $this->assertSame($stream, $manager->getContentStream(null, 'ISO-8859-1', 'WINDOWS-1252'));
        $this->assertSame($stream, $manager->getContentStream(null, 'WINDOWS-1252', 'UTF-8'));
    }
    
    public function testAttachCharsetConversionAndTransferEncodingDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamDecoratorFactory->expects($this->exactly(1))
            ->method('newCharsetStreamDecorator')
            ->with($stream, 'US-ASCII', 'UTF-8')
            ->willReturn($stream);
        $this->mockStreamDecoratorFactory->expects($this->exactly(1))
            ->method('newQuotedPrintableStreamDecorator')
            ->with($stream)
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);

        $manager = $this->partStreamFilterManager;
        $this->assertSame($stream, $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8'));
        $this->assertSame($stream, $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8'));
        $this->assertSame($stream, $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8'));
    }
    
    /*public function testReset()
    {
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                ++$callCount;
            }
        );
        
        $closeCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCloseCallback(
            function ($filtername, $params) use (&$closeCount) {
                ++$closeCount;
            }
        );

        $manager = $this->partStreamFilterManager;
        $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8');
        $manager->reset();

        $this->assertEquals(2, $callCount);
        $this->assertEquals(2, $closeCount);
        
        $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8');
        
        $this->assertEquals(4, $callCount);
        $this->assertEquals(2, $closeCount);
    }
    
    public function testResetByAttachingDifferentHandle()
    {
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                ++$callCount;
            }
        );
        
        $closeCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCloseCallback(
            function ($filtername, $params) use (&$closeCount) {
                ++$closeCount;
            }
        );

        $manager = $this->partStreamFilterManager;
        $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-16');
        $manager->setContentUrl('php://temp');
        $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-16');

        $this->assertEquals(4, $callCount);
        $this->assertEquals(2, $closeCount);
    }*/
}
