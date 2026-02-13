<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * PartHeaderContainerFactoryTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(PartHeaderContainerFactory::class)]
#[Group('PartHeaderContainerFactory')]
#[Group('MessagePart')]
class PartHeaderContainerFactoryTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $mockhf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\HeaderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new PartHeaderContainerFactory(
            \mmpGetTestLogger(),
            $mockhf
        );
    }

    public function testNewInstance() : void
    {
        $container = $this->instance->newInstance();
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Message\PartHeaderContainer::class,
            $container
        );
    }
}
