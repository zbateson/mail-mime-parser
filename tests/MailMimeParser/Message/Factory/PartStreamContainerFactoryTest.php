<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;

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
    private $instance;

    protected function setUp() : void
    {
        $mocksdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->instance = new PartStreamContainerFactory(
            $mocksdf
        );
    }

    public function testNewInstance()
    {
        $container = $this->instance->newInstance();
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\PartStreamContainer',
            $container
        );
    }
}
