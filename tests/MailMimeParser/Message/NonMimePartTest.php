<?php
namespace ZBateson\MailMimeParser\Message;

use LegacyPHPUnit\TestCase;

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
    private $nonMimePart;

    protected function legacySetUp()
    {
        $this->nonMimePart = $this->getMockForAbstractClass(
            'ZBateson\MailMimeParser\Message\NonMimePart',
            [],
            '',
            false
        );
    }

    public function testIsTextPart()
    {
        $this->assertTrue($this->nonMimePart->isTextPart());
    }

    public function testGetContentType()
    {
        $this->assertEquals('text/plain', $this->nonMimePart->getContentType());
    }

    public function testGetCharset()
    {
        $this->assertEquals('ISO-8859-1', $this->nonMimePart->getCharset());
    }

    public function testGetContentDisposition()
    {
        $this->assertEquals('inline', $this->nonMimePart->getContentDisposition());
    }

    public function testGetContentTransferEncoding()
    {
        $this->assertEquals('7bit', $this->nonMimePart->getContentTransferEncoding());
    }

    public function testIsMime()
    {
        $this->assertFalse($this->nonMimePart->isMime());
    }

    public function testGetContentId()
    {
        $this->assertNull($this->nonMimePart->getContentId());
    }
}
