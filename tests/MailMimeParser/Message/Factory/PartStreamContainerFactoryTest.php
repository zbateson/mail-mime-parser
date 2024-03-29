<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\MailMimeParser;

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
        // using container because LoggerInterface is '#[Inject]'ed
        $container = MailMimeParser::getGlobalContainer();
        $this->instance = $container->get(PartStreamContainerFactory::class);
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
