<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of UUEncodedPartTest
 *
 * @group UUEncodedPart
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\UUEncodedPart
 * @author Zaahid Bateson
 */
class UUEncodedPartTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $part = new UUEncodedPart(
            'handle',
            'contentHandle',
            ['mode' => 0644, 'filename' => 'bin-bashy.jpg']
        );
        $this->assertFalse($part->isTextPart());
        $this->assertFalse($part->isMime());
        $this->assertEquals('application/octet-stream', $part->getContentType());
        $this->assertEquals('attachment', $part->getContentDisposition());
        $this->assertEquals('x-uuencode', $part->getContentTransferEncoding());
        $this->assertEquals(0644, $part->getUnixFileMode());
        $this->assertEquals('bin-bashy.jpg', $part->getFilename());
    }
}
