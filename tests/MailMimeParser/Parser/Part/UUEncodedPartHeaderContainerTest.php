<?php
namespace ZBateson\MailMimeParser\Parser\Part;

use PHPUnit\Framework\TestCase;

/**
 * UUEncodedPartHeaderContainerTest
 *
 * @group UUEncodedPartHeaderContainer
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\Part\UUEncodedPartHeaderContainer
 * @author Zaahid Bateson
 */
class UUEncodedPartHeaderContainerTest extends TestCase
{
    private $instance;

    protected function setUp() : void
    {
        $hf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new UUEncodedPartHeaderContainer($hf);
    }

    public function testGetSetUnixFileMode()
    {
        $this->instance->setUnixFileMode(0777);
        $this->assertSame(0777, $this->instance->getUnixFileMode());
    }

    public function testGetSetFilename()
    {
        $this->instance->setFilename('test0r');
        $this->assertSame('test0r', $this->instance->getFilename());
    }
}
