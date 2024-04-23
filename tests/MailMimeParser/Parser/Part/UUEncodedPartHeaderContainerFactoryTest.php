<?php

namespace ZBateson\MailMimeParser\Parser\Part;

use PHPUnit\Framework\TestCase;

/**
 * UUEncodedPartHeaderContainerFactoryTest
 *
 * @group UUEncodedPartHeaderContainerFactory
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\Part\UUEncodedPartHeaderContainerFactory
 * @author Zaahid Bateson
 */
class UUEncodedPartHeaderContainerFactoryTest extends TestCase
{
  // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $hf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\HeaderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new UUEncodedPartHeaderContainerFactory(\mmpGetTestLogger(), $hf);
    }

    public function testNewInstance() : void
    {
        $ob = $this->instance->newInstance(0777, 'test0r');
        $this->assertInstanceOf(
            UUEncodedPartHeaderContainer::class,
            $ob
        );
        // make sure params are passed
        $this->assertSame(0777, $ob->getUnixFileMode());
        $this->assertSame('test0r', $ob->getFilename());
    }
}
