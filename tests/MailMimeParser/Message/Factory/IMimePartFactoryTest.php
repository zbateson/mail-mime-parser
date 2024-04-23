<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Stream\MessagePartStream;

/**
 * IMimePartFactoryTest
 *
 * @group IMimePartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Factory\IMimePartFactory
 * @covers ZBateson\MailMimeParser\Message\Factory\IMessagePartFactory
 * @author Zaahid Bateson
 */
class IMimePartFactoryTest extends TestCase
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
        $phc = $this->getMockForFactoryExpectsOnce(PartHeaderContainerFactory::class, \ZBateson\MailMimeParser\Message\PartHeaderContainer::class);
        $pcc = $this->getMockForFactoryExpectsOnce(PartChildrenContainerFactory::class, \ZBateson\MailMimeParser\Message\PartChildrenContainer::class);

        $sdf = $this->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $msp = $this->getMockBuilder(MessagePartStream::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sdf->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Message\MimePart::class))
            ->willReturn($msp);

        $instance = new IMimePartFactory(\mmpGetTestLogger(), $sdf, $psc, $phc, $pcc);
        $part = $instance->newInstance();
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Message\MimePart::class,
            $part
        );
    }
}
