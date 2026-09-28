<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Message\HeaderBudget;

/**
 * PartHeaderContainerFactoryTest
 *
 * @group PartHeaderContainerFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory
 * @author Zaahid Bateson
 */
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

    public function testNewInstanceCreatesBudget() : void
    {
        $container = $this->instance->newInstance();
        $this->assertSame(250000, $container->getBudget()->getMaxTokenCount());
        $this->assertSame(50000, $container->getBudget()->getMaxHeaderCount());
        $this->assertSame(8388608, $container->getBudget()->getMaxSizeBytes());

        $mockhf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\HeaderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factory = new PartHeaderContainerFactory(\mmpGetTestLogger(), $mockhf, 12, 34, 56);
        $this->assertSame(12, $factory->newInstance()->getBudget()->getMaxTokenCount());
        $this->assertSame(34, $factory->newInstance()->getBudget()->getMaxHeaderCount());
        $this->assertSame(56, $factory->newInstance()->getBudget()->getMaxSizeBytes());
    }

    public function testNewInstanceFromSharesBudget() : void
    {
        $source = $this->instance->newInstance();
        $container = $this->instance->newInstance($source);
        $this->assertSame($source->getBudget(), $container->getBudget());
    }

    public function testNewInstanceWithBudget() : void
    {
        $budget = new HeaderBudget(7, 8, 9);
        $container = $this->instance->newInstance(null, $budget);
        $this->assertSame($budget, $container->getBudget());
    }
}
