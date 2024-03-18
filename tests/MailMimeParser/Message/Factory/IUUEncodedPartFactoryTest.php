<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * IUUEncodedPartFactoryTest
 *
 * @group IUUEncodedPartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Factory\IUUEncodedPartFactory
 * @covers ZBateson\MailMimeParser\Message\Factory\IMessagePartFactory
 * @author Zaahid Bateson
 */
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
        $psc = $this->getMockForFactoryExpectsOnce(\ZBateson\MailMimeParser\Message\Factory\PartStreamContainerFactory::class, \ZBateson\MailMimeParser\Message\PartStreamContainer::class);

        $sdf = $this->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sdf->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Message\UUEncodedPart::class))
            ->willReturn(Psr7\Utils::streamFor('test'));

        $instance = new IUUEncodedPartFactory($sdf, $psc);
        $part = $instance->newInstance();
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Message\UUEncodedPart::class,
            $part
        );
    }
}
