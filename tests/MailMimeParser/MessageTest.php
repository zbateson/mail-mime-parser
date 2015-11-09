<?php

use ZBateson\MailMimeParser\Message;

/**
 * Description of MessageTest
 *
 * @group Message
 * @author Zaahid Bateson
 */
class MessageTest extends \PHPUnit_Framework_TestCase
{
    protected function getMockedPart()
    {
        $part = $this->getMockBuilder('ZBateson\MailMimeParser\Part')
            ->disableOriginalConstructor()
            ->setMethods(['setRawHeader', 'getHeader', 'getHeaderValue', 'getHeaderParameter'])
            ->getMock();
        return $part;
    }
    
    protected function getMockedHeaderFactory()
    {
        $headerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        return $headerFactory;
    }

    public function testAddHtmlPart()
    {
        $hf = $this->getMockedHeaderFactory();
        $part = $this->getMockedPart();
        $part->method('getHeaderValue')->willReturn('text/html');

        $message = new Message($hf);
        $message->addPart($part);
        $this->assertNull($message->getTextPart());
        $this->assertNull($message->getAttachmentPart(0));
        $this->assertSame($part, $message->getHtmlPart());
    }

    public function testAddTextPart()
    {
        $hf = $this->getMockedHeaderFactory();
        $part = $this->getMockedPart();
        $part->method('getHeaderValue')->willReturn('text/plain');

        $message = new Message($hf);
        $message->addPart($part);
        $this->assertNull($message->getHtmlPart());
        $this->assertNull($message->getAttachmentPart(0));
        $this->assertSame($part, $message->getTextPart());
    }

    public function testAddAttachmentPart()
    {
        $hf = $this->getMockedHeaderFactory();
        $part = $this->getMockedPart();
        $part->method('getHeaderValue')->willReturn('image/png');

        $message = new Message($hf);
        $message->addPart($part);
        $this->assertNull($message->getHtmlPart());
        $this->assertNull($message->getTextPart());
        $this->assertSame($part, $message->getAttachmentPart(0));
    }
}
