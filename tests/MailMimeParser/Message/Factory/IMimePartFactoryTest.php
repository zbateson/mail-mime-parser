<?php

namespace ZBateson\MailMimeParser\Message\Factory;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * IMimePartFactoryTest
 *
 * @group IMimePartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Parser\Part\IMimePartFactory
 * @covers ZBateson\MailMimeParser\Parser\Part\IMessagePartFactory
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
        $psc = $this->getMockForFactoryExpectsOnce(\ZBateson\MailMimeParser\Message\Factory\PartStreamContainerFactory::class, \ZBateson\MailMimeParser\Message\PartStreamContainer::class);
        $phc = $this->getMockForFactoryExpectsOnce(\ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory::class, \ZBateson\MailMimeParser\Message\PartHeaderContainer::class);
        $pcc = $this->getMockForFactoryExpectsOnce(\ZBateson\MailMimeParser\Message\Factory\PartChildrenContainerFactory::class, \ZBateson\MailMimeParser\Message\PartChildrenContainer::class);

        $sdf = $this->getMockBuilder(\ZBateson\MailMimeParser\Stream\StreamFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sdf->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\\' . \ZBateson\MailMimeParser\Message\MimePart::class))
            ->willReturn(Psr7\Utils::streamFor('test'));

        $instance = new IMimePartFactory($sdf, $psc, $phc, $pcc);
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Container\IService::class, $instance);
        $part = $instance->newInstance();
        $this->assertInstanceOf(
            '\\' . \ZBateson\MailMimeParser\Message\MimePart::class,
            $part
        );
    }
}
