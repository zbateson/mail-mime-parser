<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * PartStreamContainerFactoryTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(PartStreamContainerFactoryTest::class)]
#[Group('PartStreamContainerFactory')]
#[Group('MessagePart')]
class PartStreamContainerFactoryTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $instance;

    protected function setUp() : void
    {
        $mocksdf = $this->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new PartStreamContainerFactory(
            \mmpGetTestLogger(),
            $mocksdf,
            new MbWrapper(),
            true
        );
    }

    public function testNewInstance() : void
    {
        $container = $this->instance->newInstance();
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Message\PartStreamContainer::class,
            $container
        );
    }
}
