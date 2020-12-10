<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use LegacyPHPUnit\TestCase;

/**
 * Description of AddressGroupConsumerTest
 *
 * @group Consumers
 * @group AddressGroupConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AddressGroupConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class AddressGroupConsumerTest extends TestCase
{
    private $addressGroupConsumer;

    protected function legacySetUp()
    {
        $charsetConverter = $this->getMockBuilder('ZBateson\MbWrapper\MbWrapper')
			->setMethods(['__toString'])
			->getMock();
        $pf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\HeaderPartFactory')
			->setConstructorArgs([$charsetConverter])
			->setMethods(['__toString'])
			->getMock();
        $mlpf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory')
			->setConstructorArgs([$charsetConverter])
			->setMethods(['__toString'])
			->getMock();
        $cs = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Consumer\ConsumerService')
			->setConstructorArgs([$pf, $mlpf])
			->setMethods(['__toString'])
			->getMock();
        $this->addressGroupConsumer = new AddressGroupConsumer($cs, $pf);
    }

    public function testConsumeGroup()
    {
        $group = 'Wilfred, Emma';
        $ret = $this->addressGroupConsumer->__invoke($group);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressGroupPart', $ret[0]);
        $this->assertEquals('Wilfred', $ret[0]->getAddress(0)->getEmail());
        $this->assertEquals('Emma', $ret[0]->getAddress(1)->getEmail());
    }

    public function testConsumeGroupWithinGroup()
    {
        $group = 'Wilfred, Bubba: One, Two';
        $ret = $this->addressGroupConsumer->__invoke($group);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressGroupPart', $ret[0]);
        $this->assertEquals('Wilfred', $ret[0]->getAddress(0)->getEmail());
        $this->assertEquals('One', $ret[0]->getAddress(1)->getEmail());
        $this->assertEquals('Two', $ret[0]->getAddress(2)->getEmail());
    }
}
