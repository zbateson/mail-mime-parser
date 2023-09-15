<?php

namespace ZBateson\MailMimeParser\Container;

use PHPUnit\Framework\TestCase;
use Pimple\Exception\UnknownIdentifierException;
use ZBateson\MailMimeParser\ServiceLocator;

/**
 * Description of AutoServiceContainerTest
 *
 * @group AutoServiceContainer
 * @group Container
 * @covers ZBateson\MailMimeParser\ServiceLocator
 * @covers ZBateson\MailMimeParser\Container\AutoServiceContainer
 * @author Zaahid Bateson
 */
class AutoServiceContainerTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $container;

    protected function setUp() : void
    {
        $this->container = ServiceLocator::newInstance();
    }

    public function testSetAndGet() : void
    {
        $this->container['test'] = 'toost';
        $this->assertSame('toost', $this->container['test']);
    }

    public function testAutoRegister() : void
    {
        $this->assertFalse($this->container->offsetExists('blah'));
        $this->assertTrue($this->container->offsetExists('ArrayObject'));
        $this->assertInstanceOf('SplFixedArray', $this->container->offsetGet('SplFixedArray'));
        $thrown = false;
        try {
            $this->container->offsetGet('Arooo');
        } catch (UnknownIdentifierException $ex) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testAutoRegisterParams() : void
    {
        $this->container['secondArg'] = 'Aha!';
        $ob = $this->container['ZBateson\MailMimeParser\ContainerTestClass'];
        $this->assertNotNull($ob);
        $this->assertInstanceOf('ZBateson\MailMimeParser\ContainerTestClass', $ob);
        $this->assertInstanceOf('SplFixedArray', $ob->firstArg);
        $this->assertSame('Aha!', $ob->secondArg);
    }
}
