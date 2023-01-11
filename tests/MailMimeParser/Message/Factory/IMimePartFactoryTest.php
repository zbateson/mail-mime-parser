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

    public function testNewInstance()
    {
        $psc = $this->getMockForFactoryExpectsOnce('ZBateson\MailMimeParser\Message\Factory\PartStreamContainerFactory', 'ZBateson\MailMimeParser\Message\PartStreamContainer');
        $phc = $this->getMockForFactoryExpectsOnce('ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory', 'ZBateson\MailMimeParser\Message\PartHeaderContainer');
        $pcc = $this->getMockForFactoryExpectsOnce('ZBateson\MailMimeParser\Message\Factory\PartChildrenContainerFactory', 'ZBateson\MailMimeParser\Message\PartChildrenContainer');

        $sdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $sdf->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Message\MimePart'))
            ->willReturn(Psr7\Utils::streamFor('test'));

        $instance = new IMimePartFactory($sdf, $psc, $phc, $pcc);
        $part = $instance->newInstance();
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\MimePart',
            $part
        );
    }
}
