<?php
namespace ZBateson\MailMimeParser;

use PHPUnit_Framework_TestCase;

/**
 * Description of MimePartFactoryTest
 *
 * @group MimePartFactory
 * @group Base
 * @covers ZBateson\MailMimeParser\MimePartFactory
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
        $this->mimePartFactory = new MimePartFactory($headerFactory);
    }
    
    public function testNewMimePart()
    {
        $part = $this->mimePartFactory->newMimePart();
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MimePart', $part);
    }
    
    public function testNewNonMimePart()
    {
        $part = $this->mimePartFactory->newNonMimePart();
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\NonMimePart', $part);
    }
    
    public function testNewUUEncodedPart()
    {
        $part = $this->mimePartFactory->newUUEncodedPart(066, 'test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\UUEncodedPart', $part);
    }
}
