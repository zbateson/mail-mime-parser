<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

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

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
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
        $this->addressConsumer = new AddressEmailConsumer($cs, $pf);
    }

    public function testConsumeEmail()
    {
        $email = 'Max.Payne@AddressUnknown.com';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $address);
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
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $address);
        $this->assertEquals('Max.Payne@AddressUnknown.com', $address->getEmail());
    }

    public function testConsumeEmailWithQuotes()
    {
        $email = 'Max"(imum)..Payne (comment)"@AddressUnknown.com';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $address);
        $this->assertEquals('Max"(imum)..Payne (comment)"@AddressUnknown.com', $address->getEmail());
    }

    public function testNotConsumeAddressGroup()
    {
        $email = 'Senate: Caesar@Dictator.com,Cicero@Philosophy.com, Marc Antony <MarcAntony@imawesome.it>';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $address);
        $this->assertEquals('Senate:Caesar@Dictator.com,Cicero@Philosophy.com,MarcAntony<MarcAntony@imawesome.it', $address->getEmail());
    }
}
