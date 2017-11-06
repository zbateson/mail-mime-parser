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
    protected $partBuilder;
    
    protected $vfs;
    
    protected function setUp()
    {
        $this->vfs = vfsStream::setup('root');
        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }
    
    private function getMessagePart()
    {
        return $this->getMockBuilder(
            'ZBateson\MailMimeParser\Message\Part\MessagePart'
        )
            ->setConstructorArgs(['habibi', $this->partBuilder])
            ->getMockForAbstractClass();
    }
    
    public function testNewInstance()
    {
        $messagePart = $this->getMessagePart();
        $this->assertNotNull($messagePart);
        $this->assertFalse($messagePart->hasContent());
        $this->assertNull($messagePart->getHandle());
        $this->assertNull($messagePart->getContentResourceHandle());
        $this->assertNull($messagePart->getContent());
        $this->assertNull($messagePart->getParent());
        $this->assertEquals('habibi', $messagePart->getMessageObjectId());
    }
    
    public function testPartStreamHandle()
    {
        $fileMockPart = vfsStream::newFile('part')->at($this->vfs);
        $fileMockPart->withContent('mucha agua');
        $this->partBuilder
            ->method('getStreamPartFilename')
            ->willReturn($fileMockPart->url());
        
        $messagePart = $this->getMessagePart();
        $this->assertFalse($messagePart->hasContent());
        $this->assertNull($messagePart->getContentResourceHandle());
        $this->assertNotNull($messagePart->getHandle());
        $handle = $messagePart->getHandle();
        $this->assertEquals('mucha agua', stream_get_contents($handle));
    }
    
    public function testContentStreamHandle()
    {
        $fileMockPart = vfsStream::newFile('part')->at($this->vfs);
        $fileMockPart->withContent('mucho mas agua');
        $this->partBuilder
            ->method('getStreamContentFilename')
            ->willReturn($fileMockPart->url());
        
        $messagePart = $this->getMessagePart();
        $this->assertTrue($messagePart->hasContent());
        $this->assertNotNull($messagePart->getContentResourceHandle());
        $handle = $messagePart->getContentResourceHandle();
        $this->assertEquals('mucho mas agua', stream_get_contents($handle));
    }
    
    public function testGetContent()
    {
        $fileMockPart = vfsStream::newFile('part')->at($this->vfs);
        $fileMockPart->withContent('agua con rocas');
        $this->partBuilder
            ->method('getStreamContentFilename')
            ->willReturn($fileMockPart->url());
        $messagePart = $this->getMessagePart();
        $this->assertEquals('agua con rocas', $messagePart->getContent());
    }
    
    public function testDestructClosesHandles()
    {
        $filePart = vfsStream::newFile('part')->at($this->vfs);
        $fileContent = vfsStream::newFile('content')->at($this->vfs);
        
        $this->partBuilder
            ->method('getStreamPartFilename')
            ->willReturn($filePart->url());
        $this->partBuilder
            ->method('getStreamContentFilename')
            ->willReturn($fileContent->url());
        
        // cloned to test __destruct -- phpunit has an internal reference to
        // the mocked object.
        $messagePart = clone($this->getMessagePart());
        $handle = $messagePart->getHandle();
        $contentHandle = $messagePart->getContentResourceHandle();
        
        $this->assertTrue(is_resource($handle));
        $this->assertTrue(is_resource($contentHandle));
        
        unset($messagePart);
        
        $this->assertFalse(is_resource($handle));
        $this->assertFalse(is_resource($contentHandle));
    }
}
