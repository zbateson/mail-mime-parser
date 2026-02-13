<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * UUEncodedPartHeaderContainerTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(UUEncodedPartHeaderContainer::class)]
#[Group('UUEncodedPartHeaderContainer')]
#[Group('Parser')]
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
