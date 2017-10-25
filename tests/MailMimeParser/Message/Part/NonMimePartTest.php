<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

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
        $part = new NonMimePart('handle', 'contentHandle');
        $this->assertTrue($part->isTextPart());
        $this->assertFalse($part->isMime());
        $this->assertEquals('text/plain', $part->getContentType());
        $this->assertEquals('inline', $part->getContentDisposition());
        $this->assertEquals('7bit', $part->getContentTransferEncoding());
    }
}
