<?php

namespace ZBateson\MailMimeParser\Container;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ZBateson\MailMimeParser\ServiceLocator;

/**
 * Description of ServiceLocatorTest
 *
 * @group ServiceLocator
 * @group Base
 * @covers ZBateson\MailMimeParser\ServiceLocator
 * @author Zaahid Bateson
 */
class ServiceLocatorTest extends TestCase
{
    public function testGetGlobalInstance() : void
    {
        $o1 = ServiceLocator::getGlobalInstance();
        $o2 = ServiceLocator::getGlobalInstance();
        $this->assertInstanceOf(ServiceLocator::class, $o1);
        $this->assertSame($o1, $o2);

        ServiceLocator::setGlobalLogger(new NullLogger());
        $o3 = ServiceLocator::getGlobalInstance();
        $this->assertNotSame($o1, $o3);

        ServiceLocator::setGlobalServiceProviders([]);
        $o4 = ServiceLocator::getGlobalInstance();
        $this->assertNotSame($o1, $o4);
        $this->assertNotSame($o3, $o4);
    }
}
