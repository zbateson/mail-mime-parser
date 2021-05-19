<?php
namespace ZBateson\MailMimeParser\Message;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

/**
 * Description of UUEncodedPartTest
 *
 * @group UUEncodedPart
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\UUEncodedPart
 * @author Zaahid Bateson
 */
class UUEncodedPartTest extends TestCase
{
    private $instance;

    protected function legacySetUp()
    {
        $psc = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartStreamContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new UUEncodedPart(null, $psc);
    }

    public function testGetAndSetFileName()
    {
        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $this->instance->attach($observer);

        $this->assertNull($this->instance->getFilename());
        $this->instance->setFilename('arre');
        $this->assertEquals('arre', $this->instance->getFilename());
    }

    public function testGetAndUnixFileMode()
    {
        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $this->instance->attach($observer);

        $this->assertNull($this->instance->getUnixFileMode());
        $this->instance->setUnixFileMode(0776);
        $this->assertEquals(0776, $this->instance->getUnixFileMode());
    }

     public function testIsTextPart()
    {
        $this->assertFalse($this->instance->isTextPart());
    }

    public function testGetContentType()
    {
        $this->assertEquals('application/octet-stream', $this->instance->getContentType());
    }

    public function testGetCharset()
    {
        $this->assertEquals(null, $this->instance->getCharset());
    }

    public function testGetContentDisposition()
    {
        $this->assertEquals('attachment', $this->instance->getContentDisposition());
    }

    public function getContentTransferEncoding()
    {
        $this->assertEquals('x-uuencode', $this->instance->getContentTransferEncoding());
    }
}
