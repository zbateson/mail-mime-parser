<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Message\HeaderTokenBudget;

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

    public function testNewInstanceCreatesTokenBudget() : void
    {
        $container = $this->instance->newInstance();
        $this->assertSame(250000, $container->getTokenBudget()->getMaxTokenCount());

        $mockhf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\HeaderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factory = new PartHeaderContainerFactory(\mmpGetTestLogger(), $mockhf, 12);
        $this->assertSame(12, $factory->newInstance()->getTokenBudget()->getMaxTokenCount());
    }

    public function testNewInstanceFromSharesTokenBudget() : void
    {
        $source = $this->instance->newInstance();
        $container = $this->instance->newInstance($source);
        $this->assertSame($source->getTokenBudget(), $container->getTokenBudget());
    }

    public function testNewInstanceWithTokenBudget() : void
    {
        $budget = new HeaderTokenBudget(7);
        $container = $this->instance->newInstance(null, $budget);
        $this->assertSame($budget, $container->getTokenBudget());
    }
}
