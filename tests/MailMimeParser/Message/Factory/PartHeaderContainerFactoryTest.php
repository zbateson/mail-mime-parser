<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

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
            new NullLogger(),
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
