<?php
namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;

/**
 * Description of NonMimePartTest
 *
 * @group NonMimePart
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\NonMimePart
 * @author Zaahid Bateson
 */
class NonMimePartTest extends TestCase
{
    private $instance;

    protected function setUp() : void
    {
        $this->instance = $this->getMockForAbstractClass(
            'ZBateson\MailMimeParser\Message\NonMimePart',
            [],
            '',
            false
        );
    }

    public function testIsTextPart()
    {
        $this->assertTrue($this->instance->isTextPart());
    }

    public function testGetContentType()
    {
        $this->assertEquals('text/plain', $this->instance->getContentType());
    }

    public function testGetCharset()
    {
        $this->assertEquals('ISO-8859-1', $this->instance->getCharset());
    }

    public function testGetContentDisposition()
    {
        $this->assertEquals('inline', $this->instance->getContentDisposition());
    }

    public function testGetContentTransferEncoding()
    {
        $this->assertEquals('7bit', $this->instance->getContentTransferEncoding());
    }

    public function testIsMime()
    {
        $this->assertFalse($this->instance->isMime());
    }

    public function testGetContentId()
    {
        $this->assertNull($this->instance->getContentId());
    }
}
