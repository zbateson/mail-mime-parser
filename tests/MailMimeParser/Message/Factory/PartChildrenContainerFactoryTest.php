<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * PartChildrenContainerFactoryTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(PartHeaderContainerFactory::class)]
#[Group('PartChildrenContainerFactory')]
#[Group('MessagePart')]
class PartChildrenContainerFactoryTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $this->instance = new PartChildrenContainerFactory();
    }

    public function testNewInstance() : void
    {
        $container = $this->instance->newInstance();
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Message\PartChildrenContainer::class,
            $container
        );
    }
}
