<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Stream\MessagePartStreamDecorator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * IUUEncodedPartFactoryTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(IUUEncodedPartFactory::class)]
#[CoversClass(IMessagePartFactory::class)]
#[Group('IUUEncodedPartFactory')]
#[Group('MessagePart')]
class IUUEncodedPartFactoryTest extends TestCase
{
    private function getMockForFactoryExpectsOnce($factoryCls, $obCls)
    {
        $fac = $this->getMockBuilder($factoryCls)
            ->disableOriginalConstructor()
            ->getMock();
        $ob = $this->getMockBuilder($obCls)
            ->disableOriginalConstructor()
            ->getMock();
        $fac->expects($this->once())->method('newInstance')->willReturn($ob);
        return $fac;
    }

    public function testNewInstance() : void
    {
        $psc = $this->getMockForFactoryExpectsOnce(PartStreamContainerFactory::class, \ZBateson\MailMimeParser\Message\PartStreamContainer::class);

        $sdf = $this->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $msp = $this->getMockBuilder(MessagePartStreamDecorator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sdf->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Message\UUEncodedPart::class))
            ->willReturn($msp);

        $instance = new IUUEncodedPartFactory(\mmpGetTestLogger(), $sdf, $psc);
        $part = $instance->newInstance();
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Message\UUEncodedPart::class,
            $part
        );
    }
}
