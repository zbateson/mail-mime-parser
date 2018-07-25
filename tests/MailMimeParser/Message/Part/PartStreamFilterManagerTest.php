<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Psr7;
use ZBateson\StreamDecorators\NonClosingStream;

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
    private $mockStreamFactory = null;
    
    protected function setUp()
    {
        $mocksdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->getMock();
        $this->partStreamFilterManager = new PartStreamFilterManager($mocksdf);
        $this->mockStreamFactory = $mocksdf;
    }
    
    public function testAttachQuotedPrintableDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newQuotedPrintableStream')
            ->with($stream)
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);
        $managerStream = $this->partStreamFilterManager->getContentStream('quoted-printable', null, null);
        $this->assertInstanceOf('\GuzzleHttp\Psr7\CachingStream', $managerStream);
        $this->assertEquals('test', $managerStream->getContents());
    }
    
    public function testAttachBase64Decoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newBase64Stream')
            ->with($stream)
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);
        $managerStream = $this->partStreamFilterManager->getContentStream('base64', null, null);
        $this->assertInstanceOf('\GuzzleHttp\Psr7\CachingStream', $managerStream);
        $this->assertEquals('test', $managerStream->getContents());
    }
    
    public function testAttachUUEncodeDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newUUStream')
            ->with($stream)
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);
        $managerStream = $this->partStreamFilterManager->getContentStream('x-uuencode', null, null);
        $this->assertInstanceOf('\GuzzleHttp\Psr7\CachingStream', $managerStream);
        $this->assertEquals('test', $managerStream->getContents());
    }
    
    public function testAttachCharsetConversionDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newCharsetStream')
            ->with($stream, 'US-ASCII', 'UTF-8')
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);
        $managerStream = $this->partStreamFilterManager->getContentStream(null, 'US-ASCII', 'UTF-8');
        $this->assertInstanceOf('\GuzzleHttp\Psr7\CachingStream', $managerStream);
        $this->assertEquals('test', $managerStream->getContents());
    }
    
    public function testReAttachTransferEncodingDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newQuotedPrintableStream')
            ->with($stream)
            ->willReturn($stream);
        $stream->rewind();
        
        $stream2 = Psr7\stream_for('test2');
        $stream3 = Psr7\stream_for('test3');
        $this->mockStreamFactory->expects($this->exactly(2))
            ->method('newUUStream')
            ->with($stream)
            ->willReturnOnConsecutiveCalls($stream2, $stream3);
        $this->partStreamFilterManager->setStream($stream);

        $manager = $this->partStreamFilterManager;
        $this->assertEquals('test2', $manager->getContentStream('x-uuencode', null, null)->getContents());
        $this->assertEquals('test2', $manager->getContentStream('x-uuencode', null, null)->getContents());
        $this->assertEquals('test2', $manager->getContentStream('x-uuencode', null, null)->getContents());

        $this->assertEquals('test', $manager->getContentStream('quoted-printable', null, null)->getContents());
        $this->assertEquals('test', $manager->getContentStream('quoted-printable', null, null)->getContents());

        $this->assertEquals('test3', $manager->getContentStream('x-uuencode', null, null)->getContents());
    }
    
    public function testReAttachCharsetConversionDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamFactory->expects($this->exactly(4))
            ->method('newCharsetStream')
            ->withConsecutive(
                [$stream, 'US-ASCII', 'UTF-8'],
                [$stream, 'US-ASCII', 'WINDOWS-1252'],
                [$stream, 'ISO-8859-1', 'WINDOWS-1252'],
                [$stream, 'WINDOWS-1252', 'UTF-8']
            )
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);

        $manager = $this->partStreamFilterManager;
        $this->assertEquals('test', $manager->getContentStream(null, 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream(null, 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream(null, 'US-ASCII', 'WINDOWS-1252')->getContents());
        $this->assertEquals('test', $manager->getContentStream(null, 'ISO-8859-1', 'WINDOWS-1252')->getContents());
        $this->assertEquals('test', $manager->getContentStream(null, 'ISO-8859-1', 'WINDOWS-1252')->getContents());
        $this->assertEquals('test', $manager->getContentStream(null, 'WINDOWS-1252', 'UTF-8')->getContents());
    }
    
    public function testAttachCharsetConversionAndTransferEncodingDecoder()
    {
        $stream = Psr7\stream_for('test');
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newCharsetStream')
            ->with($this->anything(), 'US-ASCII', 'UTF-8')
            ->willReturn($stream);
        $this->mockStreamFactory->expects($this->exactly(1))
            ->method('newQuotedPrintableStream')
            ->with($stream)
            ->willReturn($stream);
        $this->partStreamFilterManager->setStream($stream);

        $manager = $this->partStreamFilterManager;
        $this->assertEquals('test', $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8')->getContents());
        $this->assertEquals('test', $manager->getContentStream('quoted-printable', 'US-ASCII', 'UTF-8')->getContents());
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
