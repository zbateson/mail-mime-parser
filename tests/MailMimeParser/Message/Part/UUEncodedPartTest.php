<?php
namespace ZBateson\MailMimeParser\Message\Part;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

/**
 * Description of UUEncodedPartTest
 *
 * @group UUEncodedPart
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\UUEncodedPart
 * @author Zaahid Bateson
 */
class UUEncodedPartTest extends TestCase
{
    public function testInstance()
    {
        $mgr = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $sf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $pb->expects($this->exactly(2))
            ->method('getProperty')
            ->willReturnCallback(function ($param) {
                $return = ['filename' => 'wubalubadubduuuuuub!', 'mode' => 0666];
                $this->assertArrayHasKey($param, $return);
                return $return[$param];
            });

        $part = new UUEncodedPart(
            $mgr,
            $sf,
            $pb,
            Psr7\stream_for('Stuff')
        );
        $this->assertFalse($part->isTextPart());
        $this->assertFalse($part->isMime());
        $this->assertEquals('application/octet-stream', $part->getContentType());
        $this->assertEquals('attachment', $part->getContentDisposition());
        $this->assertEquals('attachment', $part->getContentDisposition());
        $this->assertEquals('x-uuencode', $part->getContentTransferEncoding());
        $this->assertNull($part->getCharset());
        $this->assertEquals(0666, $part->getUnixFileMode());
        $this->assertEquals('wubalubadubduuuuuub!', $part->getFilename());

        $part->setUnixFileMode(0444);
        $part->setFilename('wiggidywamwamwazzle!');

        $this->assertEquals(0444, $part->getUnixFileMode());
        $this->assertEquals('wiggidywamwamwazzle!', $part->getFilename());
    }
}
