<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7;

/**
 * Description of NonMimePartTest
 *
 * @group NonMimePart
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\NonMimePart
 * @author Zaahid Bateson
 */
class NonMimePartTest extends TestCase
{
    public function testInstance()
    {
        $mgr = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $sf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $part = new NonMimePart($mgr, $sf);
        $this->assertTrue($part->isTextPart());
        $this->assertFalse($part->isMime());
        $this->assertEquals('text/plain', $part->getContentType());
        $this->assertEquals('inline', $part->getContentDisposition());
        $this->assertEquals('7bit', $part->getContentTransferEncoding());
        $this->assertEquals('ISO-8859-1', $part->getCharset());
    }
}
