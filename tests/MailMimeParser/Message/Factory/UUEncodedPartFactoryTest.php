<?php
namespace ZBateson\MailMimeParser\Message\Factory;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

/**
 * UUEncodedPartFactoryTest
 *
 * @group UUEncodedPartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Parser\Part\UUEncodedPartFactory
 * @covers ZBateson\MailMimeParser\Parser\Part\MessagePartFactory
 * @author Zaahid Bateson
 */
class UUEncodedPartFactoryTest extends TestCase
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

        $sdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $sdf->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Message\UUEncodedPart'))
            ->willReturn(Psr7\stream_for('test'));

        $instance = new UUEncodedPartFactory($sdf, $psc);
        $part = $instance->newInstance();
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\UUEncodedPart',
            $part
        );
    }
}
