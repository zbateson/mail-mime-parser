<?php
namespace ZBateson\MailMimeParser\Message\Factory;

use LegacyPHPUnit\TestCase;

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
    private $instance;

    protected function legacySetUp()
    {
        $this->instance = new PartChildrenContainerFactory();
    }

    public function testNewInstance()
    {
        $container = $this->instance->newInstance();
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\PartChildrenContainer',
            $container
        );
    }
}
