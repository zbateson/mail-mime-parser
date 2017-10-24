<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

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
    protected $mimePart;
    
    protected function setUp()
    {
        $this->mimePart = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->getMock();
    }
    
    private function getMessagePart($handle, $contentHandle, $mimePart)
    {
        return $this->getMockBuilder(
            'ZBateson\MailMimeParser\Message\Part\MessagePart'
        )
            ->setConstructorArgs([$handle, $contentHandle, $mimePart])
            ->getMockForAbstractClass();
    }
    
    public function testNewInstance()
    {
        $handle = fopen('php://memory', 'r');
        $contentHandle = fopen('php://memory', 'r');
        $messagePart = $this->getMessagePart($handle, $contentHandle, $this->mimePart);
        $this->assertNotNull($messagePart);
        $this->assertTrue($messagePart->hasContent());
        $this->assertSame($handle, $messagePart->getHandle());
        $this->assertSame($contentHandle, $messagePart->getContentResourceHandle());
        $this->assertSame($this->mimePart, $messagePart->getParent());
    }
    
    public function testNullContentHandle()
    {
        $handle = fopen('php://memory', 'r');
        $messagePart = $this->getMessagePart($handle, null, $this->mimePart);
        $this->assertFalse($messagePart->hasContent());
        $this->assertNull($messagePart->getContentResourceHandle());
    }
    
    public function testGetContent()
    {
        $handle = fopen('php://memory', 'r');
        $contentHandle = fopen('php://memory', 'r+');
        fwrite($contentHandle, 'Wabalaba dub-duuuuuub!');
        rewind($contentHandle);
        $messagePart = $this->getMessagePart($handle, $contentHandle, $this->mimePart);
        $this->assertEquals('Wabalaba dub-duuuuuub!', $messagePart->getContent());
    }
}
