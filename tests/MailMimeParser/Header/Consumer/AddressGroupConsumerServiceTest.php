<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use Psr\Log\NullLogger;
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
    private $logger;

    protected function setUp() : void
    {
        $this->logger = new NullLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods()
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->setMethods()
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $qscs])
            ->setMethods()
            ->getMock();
        $aecs = $this->getMockBuilder(AddressEmailConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, $qscs])
            ->setMethods()
            ->getMock();

        $this->addressGroupConsumer = new AddressGroupConsumerService($this->logger, $pf);
        new AddressConsumerService($this->logger, $mpf, $this->addressGroupConsumer, $aecs, $ccs, $qscs);
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
