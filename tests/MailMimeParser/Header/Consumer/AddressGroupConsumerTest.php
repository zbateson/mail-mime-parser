<?php

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\PartFactory;
use ZBateson\MailMimeParser\Header\Consumer\AddressGroupConsumer;

/**
 * Description of AddressGroupConsumerTest
 *
 * @group Consumers
 * @group AddressGroupConsumer
 * @author Zaahid Bateson
 */
class AddressGroupConsumerTest extends PHPUnit_Framework_TestCase
{
    private $addressGroupConsumer;
    
    public function setUp()
    {
        $pf = new PartFactory();
        $cs = new ConsumerService($pf);
        $this->addressGroupConsumer = AddressGroupConsumer::getInstance($cs, $pf);
    }
    
    public function tearDown()
    {
        unset($this->addressGroupConsumer);
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
}
