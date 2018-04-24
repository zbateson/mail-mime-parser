<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Psr7;

/**
 * Description of NonMimePartTest
 *
 * @group NonMimePart
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\NonMimePart
 * @author Zaahid Bateson
 */
class NonMimePartTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $mgr = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $part = new NonMimePart($mgr, Psr7\stream_for('blah'));
        $this->assertTrue($part->isTextPart());
        $this->assertFalse($part->isMime());
        $this->assertEquals('text/plain', $part->getContentType());
        $this->assertEquals('inline', $part->getContentDisposition());
        $this->assertEquals('7bit', $part->getContentTransferEncoding());
    }
}
