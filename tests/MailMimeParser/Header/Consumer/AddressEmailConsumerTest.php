<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use LegacyPHPUnit\TestCase;

/**
 * Description of AddressEmailConsumerTest
 *
 * @group Consumers
 * @group AddressEmailConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AddressEmailConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class AddressEmailConsumerTest extends TestCase
{
    private $addressConsumer;

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
        $this->addressConsumer = new AddressEmailConsumer($cs, $pf);
    }

    public function testConsumeEmail()
    {
        $email = 'Max.Payne@AddressUnknown.com';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressPart', $address);
        $this->assertEquals('', $address->getName());
        $this->assertEquals($email, $address->getEmail());
    }

    public function testConsumeEmailWithComments()
    {
        // can't remember any longer if this is how it should be handled
        // need to review RFC
        $email = 'Max(imum).Payne (comment)@AddressUnknown.com';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressPart', $address);
        $this->assertEquals('Max.Payne@AddressUnknown.com', $address->getEmail());
    }

    public function testConsumeEmailWithQuotes()
    {
        $email = 'Max"(imum)..Payne (comment)"@AddressUnknown.com';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressPart', $address);
        $this->assertEquals('Max"(imum)..Payne (comment)"@AddressUnknown.com', $address->getEmail());
    }

    public function testNotConsumeAddressGroup()
    {
        $email = 'Senate: Caesar@Dictator.com,Cicero@Philosophy.com, Marc Antony <MarcAntony@imawesome.it>';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\AddressPart', $address);
        $this->assertEquals('Senate:Caesar@Dictator.com,Cicero@Philosophy.com,MarcAntony<MarcAntony@imawesome.it', $address->getEmail());
    }
}
