<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ZBateson\MbWrapper\MbWrapper;

/**
 * PartStreamContainerFactoryTest
 *
 * @group PartStreamContainerFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Factory\PartStreamContainerFactoryTest
 * @author Zaahid Bateson
 */
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
