<?php

namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of NonMimePartTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(NonMimePart::class)]
#[Group('NonMimePart')]
#[Group('MessagePart')]
class NonMimePartTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $this->instance = $this->getMockForAbstractClass(
            NonMimePart::class,
            [],
            '',
            false
        );
    }

    public function testIsTextPart() : void
    {
        $this->assertTrue($this->instance->isTextPart());
    }

    public function testGetContentType() : void
    {
        $this->assertEquals('text/plain', $this->instance->getContentType());
    }

    public function testGetCharset() : void
    {
        $this->assertEquals('ISO-8859-1', $this->instance->getCharset());
    }

    public function testGetContentDisposition() : void
    {
        $this->assertEquals('inline', $this->instance->getContentDisposition());
    }

    public function testGetContentTransferEncoding() : void
    {
        $this->assertEquals('7bit', $this->instance->getContentTransferEncoding());
    }

    public function testIsMime() : void
    {
        $this->assertFalse($this->instance->isMime());
    }

    public function testGetContentId() : void
    {
        $this->assertNull($this->instance->getContentId());
    }
}
