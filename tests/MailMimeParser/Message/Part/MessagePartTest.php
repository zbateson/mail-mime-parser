<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * MessagePartFactoryTest
 * 
 * @group MessagePartClass
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\MessagePart
 * @author Zaahid Bateson
 */
class MessagePartTest extends PHPUnit_Framework_TestCase
{
    protected $partStreamFilterManager;
    protected $partBuilder;
    
    protected $vfs;
    
    protected function setUp()
    {
        $this->vfs = vfsStream::setup('root');
        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $psf = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamFilterManager = $psf;
    }
    
    private function getMessagePart($handle = 'habibi')
    {
        return $this->getMockForAbstractClass(
            'ZBateson\MailMimeParser\Message\Part\MessagePart',
            [$handle, $this->partBuilder, $this->partStreamFilterManager]
        );
    }
    
    public function testNewInstance()
    {
        $this->partBuilder->method('getStreamContentLength')->willReturn(0);
        $messagePart = $this->getMessagePart();
        $this->assertNotNull($messagePart);
        $this->assertFalse($messagePart->hasContent());
        $this->assertNull($messagePart->getContentResourceHandle());
        $this->assertNull($messagePart->getContent());
        $this->assertNull($messagePart->getParent());
        $this->assertEquals('habibi', $messagePart->getHandle());
    }
    
    public function testPartStreamHandle()
    {
        $fileMockPart = vfsStream::newFile('part')->at($this->vfs);
        $fileMockPart->withContent('mucha agua');

        $phandle = fopen($fileMockPart->url(), 'r');

        $this->partBuilder->method('getStreamContentLength')->willReturn(0);
        $messagePart = $this->getMessagePart($phandle);
        
        $this->assertFalse($messagePart->hasContent());
        $this->assertNull($messagePart->getContentResourceHandle());
        $this->assertNotNull($messagePart->getHandle());
        $handle = $messagePart->getHandle();
        $this->assertEquals('mucha agua', stream_get_contents($handle));

        fclose($phandle);
    }
    
    public function testContentStreamHandle()
    {
        $fileMockPart = vfsStream::newFile('part')->at($this->vfs);
        $fileMockPart->withContent('mucho mas agua');
        $handle = fopen($fileMockPart->url(), 'r');
        $this->partBuilder
            ->expects($this->once())
            ->method('getStreamContentLength')
            ->willReturn(10);
        $this->partStreamFilterManager
            ->expects($this->once())
            ->method('setContentUrl')
            ->with($fileMockPart->url());
        $this->partStreamFilterManager
            ->expects($this->once())
            ->method('getContentHandle')
            ->with('wubalubadub-duuuuub', 'wigidiwamwamwazzle', 'UTF-8')
            ->willReturn($handle);
        
        $messagePart = $this->getMessagePart();
        $messagePart->method('getContentTransferEncoding')
            ->willReturn('wubalubadub-duuuuub');
        $messagePart->method('getCharset')
            ->willReturn('wigidiwamwamwazzle');
        
        $this->assertTrue($messagePart->hasContent());
        $this->assertSame($handle, $messagePart->getContentResourceHandle());
        fclose($handle);
    }
    
    public function testContentStreamHandleWithCustomCharset()
    {
        $fileMockPart = vfsStream::newFile('part')->at($this->vfs);
        $fileMockPart->withContent('mucho mas agua');
        $handle = fopen($fileMockPart->url(), 'r');
        $this->partBuilder
            ->method('getStreamContentUrl')
            ->willReturn($fileMockPart->url());
        $this->partStreamFilterManager
            ->expects($this->once())
            ->method('setContentUrl')
            ->with($fileMockPart->url());
        $this->partStreamFilterManager
            ->expects($this->exactly(2))
            ->method('getContentHandle')
            ->withConsecutive(
                [$this->anything(), null, 'a-charset'],
                [$this->anything(), 'someCharset', 'a-charset']
            )
            ->willReturn($handle);
        
        $messagePart = $this->getMessagePart();
        $this->assertTrue($messagePart->hasContent());
        $this->assertSame($handle, $messagePart->getContentResourceHandle('a-charset'));
        
        $messagePart->setCharsetOverride('someCharset', true);
        $this->assertSame($handle, $messagePart->getContentResourceHandle('a-charset'));
        
        fclose($handle);
    }
    
    public function testGetContent()
    {
        $fileMockPart = vfsStream::newFile('part')->at($this->vfs);
        $fileMockPart->withContent('agua con rocas');
        $handle = fopen($fileMockPart->url(), 'r');
        $this->partBuilder
            ->method('getStreamContentUrl')
            ->willReturn($fileMockPart->url());
        $this->partStreamFilterManager
            ->expects($this->once())
            ->method('setContentUrl')
            ->with($fileMockPart->url());
        $this->partStreamFilterManager
            ->expects($this->once())
            ->method('getContentHandle')
            ->with('', '', 'UTF-8')
            ->willReturn($handle);
        
        $messagePart = $this->getMessagePart();
        $this->assertEquals('agua con rocas', $messagePart->getContent());
        fclose($handle);
    }
    
    public function testDestructClosesHandlesAndResetsFilters()
    {
        $filePart = vfsStream::newFile('part')->at($this->vfs);
        $fileContent = vfsStream::newFile('content')->at($this->vfs);
        $handle = fopen($fileContent->url(), 'r');
        
        $this->partBuilder
            ->method('getStreamPartUrl')
            ->willReturn($filePart->url());
        $this->partBuilder
            ->method('getStreamContentUrl')
            ->willReturn($fileContent->url());
        $this->partStreamFilterManager
            ->expects($this->once())
            ->method('getContentHandle')
            ->with('', '', 'UTF-8')
            ->willReturn($handle);
        $this->partStreamFilterManager
            ->method('setContentUrl')
            ->withConsecutive([$fileContent->url()], [null]);
        
        // cloned to test __destruct -- phpunit has an internal reference to
        // the mocked object.
        
        $messagePart = clone($this->getMessagePart());
        $partHandle = $messagePart->getHandle();
        $contentHandle = $messagePart->getContentResourceHandle();
        
        $this->assertTrue(is_resource($partHandle));
        $this->assertTrue(is_resource($contentHandle));
        
        unset($messagePart);
        
        $this->assertFalse(is_resource($partHandle));
        // $contentHandle not actually closed, but setContentUrl is called with null
        fclose($handle);
    }
}
