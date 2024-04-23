<?php

namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;

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
    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $psc = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartStreamContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new UUEncodedPart(null, null, null, \mmpGetTestLogger(), $psc);
    }

    public function testGetAndSetFileName() : void
    {
        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $this->instance->attach($observer);

        $this->assertNull($this->instance->getFilename());
        $this->instance->setFilename('arre');
        $this->assertEquals('arre', $this->instance->getFilename());
    }

    public function testGetAndUnixFileMode() : void
    {
        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $this->instance->attach($observer);

        $this->assertNull($this->instance->getUnixFileMode());
        $this->instance->setUnixFileMode(0776);
        $this->assertEquals(0776, $this->instance->getUnixFileMode());
    }

     public function testIsTextPart() : void
    {
        $this->assertFalse($this->instance->isTextPart());
    }

    public function testGetContentType() : void
    {
        $this->assertEquals('application/octet-stream', $this->instance->getContentType());
    }

    public function testGetCharset() : void
    {
        $this->assertEquals(null, $this->instance->getCharset());
    }

    public function testGetContentDisposition() : void
    {
        $this->assertEquals('attachment', $this->instance->getContentDisposition());
    }

    public function getContentTransferEncoding() : void
    {
        $this->assertEquals('x-uuencode', $this->instance->getContentTransferEncoding());
    }
}
