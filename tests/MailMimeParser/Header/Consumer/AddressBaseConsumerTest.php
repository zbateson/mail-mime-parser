<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of AddressBaseConsumerTest
 *
 * @group Consumers
 * @group AddressBaseConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AddressBaseConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class AddressBaseConsumerTest extends PHPUnit_Framework_TestCase
{
    private $addressBaseConsumer;
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $cs = new ConsumerService($pf, $mlpf);
        $this->addressBaseConsumer = AddressBaseConsumer::getInstance($cs, $pf);
    }
    
    public function testConsumeAddress()
    {
        $email = 'Max.Payne@AddressUnknown.com';
        $ret = $this->addressBaseConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        
        $address = $ret[0];
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressPart', $address);
        $this->assertEquals('', $address->getName());
        $this->assertEquals($email, $address->getEmail());
    }
    
    public function testConsumeAddresses()
    {
        $emails = 'Popeye@TheSailorMan.com, Olive@Oil.com, Brute <brute@isThatHisName.com>';
        $ret = $this->addressBaseConsumer->__invoke($emails);
        $this->assertNotEmpty($ret);
        $this->assertCount(3, $ret);
        
        $this->assertEquals('Popeye@TheSailorMan.com', $ret[0]->getEmail());
        $this->assertEquals('Olive@Oil.com', $ret[1]->getEmail());
        $this->assertEquals('Brute', $ret[2]->getName());
        $this->assertEquals('brute@isThatHisName.com', $ret[2]->getEmail());
    }
    
    public function testConsumeAddressAndGroup()
    {
        $emails = 'Tyrion Lannister <tyrion@houselannister.com>, '
            . 'Winterfell: Arya Stark <arya@winterfell.com>, robb@winterfell.com;'
            . 'jaime@houselannister.com';
        $ret = $this->addressBaseConsumer->__invoke($emails);
        $this->assertNotEmpty($ret);
        $this->assertCount(3, $ret);
        
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressPart', $ret[0]);
        $this->assertEquals('Tyrion Lannister', $ret[0]->getName());
        $this->assertEquals('tyrion@houselannister.com', $ret[0]->getEmail());
        
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressGroupPart', $ret[1]);
        $this->assertEquals('Arya Stark', $ret[1]->getAddress(0)->getName());
        $this->assertEquals('arya@winterfell.com', $ret[1]->getAddress(0)->getEmail());
        $this->assertEquals('', $ret[1]->getAddress(1)->getName());
        $this->assertEquals('robb@winterfell.com', $ret[1]->getAddress(1)->getEmail());
        
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressPart', $ret[2]);
        $this->assertEquals('jaime@houselannister.com', $ret[2]->getEmail());
    }
}
