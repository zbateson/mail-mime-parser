<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of AddressBaseConsumerServiceTest
 *
 * @group Consumers
 * @group AddressBaseConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AddressBaseConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class AddressBaseConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $addressBaseConsumer;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MbWrapperService::class)
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mlpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $cs = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\ConsumerService::class)
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
        $this->addressBaseConsumer = new AddressBaseConsumerService($cs, $pf);
    }

    public function testIsService() : void
    {
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Container\IService::class, $this->addressBaseConsumer);
    }

    public function testConsumeAddress() : void
    {
        $email = 'Max.Payne@AddressUnknown.com';
        $ret = $this->addressBaseConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $address);
        $this->assertEquals('', $address->getName());
        $this->assertEquals($email, $address->getEmail());
    }

    public function testConsumeAddresses() : void
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

    public function testConsumeNamesAndAddressesWithFunnyChars() : void
    {
        $emails = '"Popeye the Sailor" <Popeye@TheSailorMan.com>, "Olive" <Olive@Oil.com:>, Brute <brute@isThatHisName.com,>, NotCute <notcute@address.com;>';
        $ret = $this->addressBaseConsumer->__invoke($emails);
        $this->assertNotEmpty($ret);
        $this->assertCount(4, $ret);

        $this->assertEquals('Popeye@TheSailorMan.com', $ret[0]->getEmail());
        $this->assertEquals('Olive@Oil.com:', $ret[1]->getEmail());
        $this->assertEquals('Brute', $ret[2]->getName());
        $this->assertEquals('brute@isThatHisName.com,', $ret[2]->getEmail());
        $this->assertEquals('notcute@address.com;', $ret[3]->getEmail());
    }

    public function testConsumeAddressAndGroup() : void
    {
        $emails = 'Tyrion Lannister <tyrion@houselannister.com>, '
            . 'Winterfell: Arya Stark <arya@winterfell.com>, robb@winterfell.com;'
            . 'jaime@houselannister.com';
        $ret = $this->addressBaseConsumer->__invoke($emails);
        $this->assertNotEmpty($ret);
        $this->assertCount(3, $ret);

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $ret[0]);
        $this->assertEquals('Tyrion Lannister', $ret[0]->getName());
        $this->assertEquals('tyrion@houselannister.com', $ret[0]->getEmail());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressGroupPart::class, $ret[1]);
        $this->assertEquals('Arya Stark', $ret[1]->getAddress(0)->getName());
        $this->assertEquals('arya@winterfell.com', $ret[1]->getAddress(0)->getEmail());
        $this->assertEquals('', $ret[1]->getAddress(1)->getName());
        $this->assertEquals('robb@winterfell.com', $ret[1]->getAddress(1)->getEmail());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $ret[2]);
        $this->assertEquals('jaime@houselannister.com', $ret[2]->getEmail());
    }
}
