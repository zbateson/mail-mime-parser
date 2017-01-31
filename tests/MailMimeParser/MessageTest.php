<?php
namespace ZBateson\MailMimeParser;

use PHPUnit_Framework_TestCase;

/**
 * Description of MessageTest
 *
 * @group Message
 * @group Base
 * @covers ZBateson\MailMimeParser\Message
 * @author Zaahid Bateson
 */
class MessageTest extends PHPUnit_Framework_TestCase
{
    protected function getMockedPart()
    {
        $part = $this->getMockBuilder('ZBateson\MailMimeParser\Message\MimePart')
            ->disableOriginalConstructor()
            ->setMethods(['setRawHeader', 'getHeader', 'getHeaderValue', 'getHeaderParameter', 'getContentResourceHandle'])
            ->getMock();
        return $part;
    }
    
    protected function getMockedMessageWriter()
    {
        $mw = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Writer\MessageWriter')
            ->disableOriginalConstructor()
            ->getMock();
        return $mw;
    }
    
    protected function getMockedHeaderFactory()
    {
        $headerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        return $headerFactory;
    }
    
    protected function getMockedPartFactory()
    {
        $partFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\MimePartFactory')
            ->disableOriginalConstructor()
            ->getMock();
        return $partFactory;
    }
    
    protected function createNewMessage()
    {
        $hf = $this->getMockedHeaderFactory();
        $mw = $this->getMockedMessageWriter();
        $pf = $this->getMockedPartFactory();
        return new Message($hf, $mw, $pf);
    }

    public function testObjectId()
    {
        $message = $this->createNewMessage();
        $message2 = $this->createNewMessage();
        $this->assertNotEmpty($message->getObjectId());
        $this->assertSame($message->getObjectId(), $message->getObjectId());
        $this->assertSame($message2->getObjectId(), $message2->getObjectId());
        $this->assertNotSame($message->getObjectId(), $message2->getObjectId());
    }
    
    public function testAddHtmlPart()
    {
        $part = $this->getMockedPart();
        $part->method('getHeaderValue')->will($this->returnCallback(function($param) {
            if ($param === 'Content-Type') {
                return 'text/html';
            }
            return null;
        }));
        $part->method('getContentResourceHandle')->willReturn('handle');

        $message = $this->createNewMessage();
        $message->addPart($part);
        
        $this->assertNull($message->getTextPart());
        $this->assertNull($message->getAttachmentPart(0));
        $this->assertSame($part, $message->getPartByMimeType('text/html'));
        $this->assertSame($part, $message->getHtmlPart());
        $this->assertEquals('handle', $message->getHtmlStream());
        $this->assertNull($message->getTextStream());
    }

    public function testAddTextPart()
    {
        $part = $this->getMockedPart();
        $part->method('getHeaderValue')->will($this->returnCallback(function($param) {
            if ($param === 'Content-Type') {
                return 'text/plain';
            }
            return null;
        }));
        $part->method('getContentResourceHandle')->willReturn('handle');

        $message = $this->createNewMessage();
        $message->addPart($part);
        $this->assertNull($message->getHtmlPart());
        $this->assertNull($message->getAttachmentPart(0));
        $this->assertSame($part, $message->getPartByMimeType('text/plain'));
        $this->assertSame($part, $message->getTextPart());
        $this->assertEquals('handle', $message->getTextStream());
        $this->assertNull($message->getHtmlStream());
    }

    public function testAddAttachmentPart()
    {
        $part = $this->getMockedPart();
        $part->method('getHeaderValue')->willReturn('image/png');

        $message = $this->createNewMessage();
        $message->addPart($part);
        $this->assertNull($message->getHtmlPart());
        $this->assertNull($message->getTextPart());
        $this->assertEquals(1, $message->getAttachmentCount());
        $this->assertSame($part, $message->getAttachmentPart(0));
        $this->assertEquals([$part], $message->getAllAttachmentParts());
    }
    
    public function testGetParts()
    {
        $part = $this->getMockedPart();
        $part->method('getHeaderValue')->willReturn('image/png');
        
        $part2 = $this->getMockedPart();
        $part2->method('getHeaderValue')->will($this->returnCallback(function($param) {
            if ($param === 'Content-Type') {
                return 'text/html';
            }
            return null;
        }));

        $message = $this->createNewMessage();
        $message->addPart($part);
        $message->addPart($part2);
        $this->assertNull($message->getTextPart());
        $this->assertSame($part2, $message->getHtmlPart());
        $this->assertEquals(2, $message->getPartCount());
        $this->assertSame($part, $message->getPart(0));
        $this->assertSame($part2, $message->getPart(1));
        $this->assertEquals([$part, $part2], $message->getAllParts());
    }
    
    public function testMessageIsMime()
    {
        $message = $this->createNewMessage();
        $this->assertFalse($message->isMime());
    }
}
