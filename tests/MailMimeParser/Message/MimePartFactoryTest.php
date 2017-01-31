<?php
namespace ZBateson\MailMimeParser\Message;

use PHPUnit_Framework_TestCase;

/**
 * Description of MimePartFactoryTest
 *
 * @group MimePartFactory
 * @group Message
 * @covers ZBateson\MailMimeParser\Message\MimePartFactory
 * @author Zaahid Bateson
 */
class MimePartFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $mimePartFactory;
    
    protected function setUp()
    {
        $headerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $messageWriterService = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Writer\MessageWriterService')
            ->disableOriginalConstructor()
            ->setMethods(['getMessageWriter'])
            ->getMock();
        $messageWriterService->method('getMessagePartWriter')->willReturn(
            $this->getMockBuilder('ZBateson\MailMimeParser\Message\Writer\MimePartWriter')
            ->disableOriginalConstructor()
            ->getMock()
        );
        $this->mimePartFactory = new MimePartFactory($headerFactory, $messageWriterService);
    }
    
    public function testNewMimePart()
    {
        $part = $this->mimePartFactory->newMimePart();
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Message\MimePart', $part);
    }
    
    public function testNewNonMimePart()
    {
        $part = $this->mimePartFactory->newNonMimePart();
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Message\NonMimePart', $part);
    }
    
    public function testNewUUEncodedPart()
    {
        $part = $this->mimePartFactory->newUUEncodedPart(066, 'test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Message\UUEncodedPart', $part);
    }
}
