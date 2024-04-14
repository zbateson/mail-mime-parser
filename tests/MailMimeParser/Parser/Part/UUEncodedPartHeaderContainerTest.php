<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

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
    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $hf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\HeaderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new UUEncodedPartHeaderContainer(\mmpGetTestLogger(), $hf);
    }

    public function testGetSetUnixFileMode() : void
    {
        $this->instance->setUnixFileMode(0777);
        $this->assertSame(0777, $this->instance->getUnixFileMode());
    }

    public function testGetSetFilename() : void
    {
        $this->instance->setFilename('test0r');
        $this->assertSame('test0r', $this->instance->getFilename());
    }
}
