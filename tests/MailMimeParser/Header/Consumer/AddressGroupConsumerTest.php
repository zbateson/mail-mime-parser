<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of AddressGroupConsumerTest
 *
 * @group Consumers
 * @group AddressGroupConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AddressGroupConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class AddressGroupConsumerTest extends PHPUnit_Framework_TestCase
{
    private $addressGroupConsumer;
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $cs = new ConsumerService($pf, $mlpf);
        $this->addressGroupConsumer = AddressGroupConsumer::getInstance($cs, $pf);
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
