<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of AddressConsumerServiceTest
 *
 * @group Consumers
 * @group AddressConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AddressConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class AddressConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
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
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$pf])
            ->setMethods(['__toString'])
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$mpf, $qscs])
            ->setMethods(['__toString'])
            ->getMock();
        $agcs = $this->getMockBuilder(AddressGroupConsumerService::class)
            ->setConstructorArgs([$pf])
            ->setMethods(['__toString', 'setAddressConsumerService'])
            ->getMock();
        $aecs = $this->getMockBuilder(AddressEmailConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, $qscs])
            ->setMethods(['__toString'])
            ->getMock();

        $agcs->expects($this->once())
            ->method('setAddressConsumerService')
            ->with($this->isInstanceOf(AddressConsumerService::class));

        $this->addressConsumer = new AddressConsumerService($pf, $agcs, $aecs, $ccs, $qscs);
    }

    public function testConsumeEmail() : void
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

    public function testConsumeEmailWithSpaces() : void
    {
        $email = "Max\n\t  .Payne@AddressUnknown.com";
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertEquals('Max.Payne@AddressUnknown.com', $ret[0]->getEmail());
    }

    public function testConsumeEmailName() : void
    {
        $email = 'Max Payne <Max.Payne@AddressUnknown.com>';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $address);
        $this->assertEquals('Max.Payne@AddressUnknown.com', $address->getEmail());
        $this->assertEquals('Max Payne', $address->getName());
    }

    public function testConsumeMimeEncodedName() : void
    {
        $email = '=?US-ASCII?Q?Kilgore_Trout?= <Kilgore.Trout@Iliyum.ny>';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $address);
        $this->assertEquals('Kilgore.Trout@Iliyum.ny', $address->getEmail());
        $this->assertEquals('Kilgore Trout', $address->getName());
    }

    public function testConsumeEmailWithComments() : void
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

    public function testConsumeEmailWithQuotes() : void
    {
        $email = 'Max"(imum)..Payne (not a comment)"@AddressUnknown.com';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressPart::class, $address);
        $this->assertEquals('Max"(imum)..Payne (not a comment)"@AddressUnknown.com', $address->getEmail());
    }

    public function testConsumeQuotedEmailLocalPartWithSpaces() : void
    {
        $email = "\"Max\n\t  .Payne\"@AddressUnknown.com";
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertEquals("\"Max\t  .Payne\"@AddressUnknown.com", $ret[0]->getEmail());
    }

    public function testConsumeVeryStrangeQuotedEmailLocalPart() : void
    {
        $email = '"very.(),:;<>[]\"  .VERY.\"very@\\\\ \"very\".unusual"@strange.example.com';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertEquals($email, $ret[0]->getEmail());
    }

    public function testConsumeAddressGroup() : void
    {
        $email = 'Senate: Caesar@Dictator.com,Cicero@Philosophy.com, Marc Antony <MarcAntony@imawesome.it>';
        $ret = $this->addressConsumer->__invoke($email);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $addressGroup = $ret[0];
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressGroupPart::class, $addressGroup);
        $this->assertEquals('Senate', $addressGroup->getName());
    }
}
