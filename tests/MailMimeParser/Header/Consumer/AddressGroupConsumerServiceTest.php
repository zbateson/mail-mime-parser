<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of AddressGroupConsumerServiceTest
 *
 * @group Consumers
 * @group AddressGroupConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AddressGroupConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class AddressGroupConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $addressGroupConsumer;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
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
        $aecs = $this->getMockBuilder(AddressEmailConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, $qscs])
            ->setMethods(['__toString'])
            ->getMock();

        $this->addressGroupConsumer = new AddressGroupConsumerService($pf);
        new AddressConsumerService($mpf, $this->addressGroupConsumer, $aecs, $ccs, $qscs);
    }

    public function testConsumeGroup() : void
    {
        $group = 'Wilfred, Emma';
        $ret = $this->addressGroupConsumer->__invoke($group);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressGroupPart::class, $ret[0]);
        $this->assertEquals('Wilfred', $ret[0]->getAddress(0)->getEmail());
        $this->assertEquals('Emma', $ret[0]->getAddress(1)->getEmail());
    }

    public function testConsumeGroupWithinGroup() : void
    {
        $group = 'Wilfred, Bubba: One, Two';
        $ret = $this->addressGroupConsumer->__invoke($group);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\AddressGroupPart::class, $ret[0]);
        $this->assertEquals('Wilfred', $ret[0]->getAddress(0)->getEmail());
        $this->assertEquals('One', $ret[0]->getAddress(1)->getEmail());
        $this->assertEquals('Two', $ret[0]->getAddress(2)->getEmail());
    }
}
