<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;

/**
 * PartChildrenContainerFactoryTest
 *
 * @group PartChildrenContainerFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory
 * @author Zaahid Bateson
 */
class PartChildrenContainerFactoryTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $this->instance = new PartChildrenContainerFactory();
    }

    public function testIsService() : void
    {
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Container\IService::class, $this->instance);
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
