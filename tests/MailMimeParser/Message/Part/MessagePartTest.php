<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;
use Exception;

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
    protected $streamFactory;
    
    protected function setUp()
    {
        $psf = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $sf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamFilterManager = $psf;
        $this->streamFactory = $sf;
    }
    
    private function getMessagePart($handle = 'habibi', $contentHandle = null)
    {
        if ($contentHandle !== null) {
            $contentHandle = Psr7\stream_for($contentHandle);
            $this->partStreamFilterManager
                ->method('setStream');
            $this->partStreamFilterManager
                ->method('getContentStream')
                ->willReturnCallback(function() use ($contentHandle) {
                    try {
                        $contentHandle->rewind();
                    } catch (Exception $e) {
                    }
                    return $contentHandle;
                });
        }
        return $this->getMockForAbstractClass(
            'ZBateson\MailMimeParser\Message\Part\MessagePart',
            [ $this->partStreamFilterManager, $this->streamFactory, Psr7\stream_for($handle), $contentHandle ]
        );
    }
    
    public function testNewInstance()
    {
        $messagePart = $this->getMessagePart();
        $this->assertNotNull($messagePart);
        $this->assertFalse($messagePart->hasContent());
        $this->assertNull($messagePart->getContentResourceHandle());
        $this->assertNull($messagePart->getContent());
        $this->assertNull($messagePart->getParent());
        $this->assertEquals('habibi', stream_get_contents($messagePart->getResourceHandle()));
    }

    public function testPartStreamHandle()
    {
        $messagePart = $this->getMessagePart('mucha agua');
        $this->assertFalse($messagePart->hasContent());
        $this->assertNull($messagePart->getContentResourceHandle());
        $this->assertNotNull($messagePart->getResourceHandle());
        $handle = $messagePart->getResourceHandle();
        $this->assertEquals('mucha agua', stream_get_contents($handle));
    }
    
    public function testContentStreamAndHandle()
    {
        $messagePart = $this->getMessagePart('Que tonta', 'Que tonto');
        $messagePart->method('getContentTransferEncoding')
            ->willReturn('wubalubadub-duuuuub');
        $messagePart->method('getCharset')
            ->willReturn('wigidiwamwamwazzle');
        
        $this->assertTrue($messagePart->hasContent());
        $this->assertEquals('Que tonto', $messagePart->getContentStream()->getContents());
        $this->assertEquals('Que tonto', stream_get_contents($messagePart->getContentResourceHandle()));
    }
    
    public function testContentStreamHandleWithCustomCharset()
    {
        $messagePart = $this->getMessagePart('Que tonta', 'Que tonto');
        $messagePart->method('getContentTransferEncoding')
            ->willReturn('quoted-printable');
        $messagePart->method('getCharset')
            ->willReturn('utf-64');

        $handle = StreamWrapper::getResource(Psr7\stream_for('Que tonto'));
        $this->partStreamFilterManager
            ->expects($this->exactly(3))
            ->method('getContentStream')
            ->withConsecutive(
                ['quoted-printable', 'utf-64', 'a-charset'],
                ['quoted-printable', 'utf-64', 'a-charset'],
                ['quoted-printable', 'override', 'UTF-8']
            )
            ->willReturn($handle);

        $this->assertTrue($messagePart->hasContent());
        $this->assertSame('Que tonto', stream_get_contents($messagePart->getContentResourceHandle('a-charset')));

        fseek($handle, 0);
        $messagePart->setCharsetOverride('someCharset', true);
        $messagePart->getContentResourceHandle('a-charset');

        $messagePart->setCharsetOverride('override');
        $messagePart->getContentResourceHandle();
    }

    public function testMarkAsChanged()
    {
        $stream = Psr7\stream_for('test');
        $messagePart = $this->getMessagePart($stream);
        $this->assertEquals($stream, $messagePart->getStream());
        
        $this->streamFactory
            ->expects($this->once())
            ->method('newMessagePartStream')
            ->with($messagePart)
            ->willReturn('Much success');

        $messagePart->markAsChanged();

        $this->assertEquals('Much success', $messagePart->getStream());
    }

    public function testGetFilenameReturnsNull()
    {
        $messagePart = $this->getMessagePart();
        $this->assertNull($messagePart->getFilename());
    }
    
    public function testGetContent()
    {
        $messagePart = $this->getMessagePart('habibi', 'sopa di agua con rocas');
        $this->assertEquals('sopa di agua con rocas', $messagePart->getContent());
    }

    public function testSaveAndToString()
    {
        $messagePart = $this->getMessagePart(
            'Demigorgon',
            Psr7\stream_for('other demons')
        );

        $handle = fopen('php://temp', 'r+');
        $messagePart->save($handle);
        rewind($handle);
        $str = stream_get_contents($handle);
        fclose($handle);

        $this->assertEquals('Demigorgon', $str);
        $this->assertEquals('Demigorgon', $messagePart->__toString());
    }

    public function testSetContentAndAttachContentStream()
    {
        $ms = Psr7\stream_for('message');
        $org = Psr7\stream_for('content');
        $part = $this->getMessagePart($ms, $org);
        $part->method('getContentTransferEncoding')
            ->willReturn('quoted-printable');
        $part->method('getCharset')
            ->willReturn('utf-64');
        
        $new = Psr7\stream_for('updated');
        $this->partStreamFilterManager
            ->method('getContentStream')
            ->withConsecutive(
                ['', 'charset', 'a-charset']
            );

        $this->assertSame($ms, $part->getStream());

        $this->partStreamFilterManager
            ->method('setStream')
            ->withConsecutive(
                [ $new ],
                [ $new ]
            );

        $part->setContent($new, 'charset');

        // actually returns $org because of method definition in getMessagePart
        $part->getContentStream('a-charset');

        $this->streamFactory
            ->expects($this->once())
            ->method('newMessagePartStream')
            ->with($part)
            ->willReturn('Much success');
        $this->assertEquals('Much success', $part->getStream());
    }
}
