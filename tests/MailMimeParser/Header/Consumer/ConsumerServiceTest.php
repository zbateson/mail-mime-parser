<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of ConsumerServiceTest
 *
 * @group Consumers
 * @group ConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\ConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class ConsumerServiceTest extends TestCase
{
    private $consumerService;

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
        $this->consumerService = new ConsumerService($pf, $mlpf);
    }

    public function testGetAddressBaseConsumer()
    {
        $consumer = $this->consumerService->getAddressBaseConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\AddressBaseConsumer::class, $consumer);
    }

    public function testGetAddressConsumer()
    {
        $consumer = $this->consumerService->getAddressConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\AddressConsumer::class, $consumer);
    }

    public function testGetAddressEmailConsumer()
    {
        $consumer = $this->consumerService->getAddressEmailConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\AddressEmailConsumer::class, $consumer);
    }

    public function testGetAddressGroupConsumer()
    {
        $consumer = $this->consumerService->getAddressGroupConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\AddressGroupConsumer::class, $consumer);
    }

    public function testGetCommentConsumer()
    {
        $consumer = $this->consumerService->getCommentConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\CommentConsumer::class, $consumer);
    }

    public function testGetGenericConsumer()
    {
        $consumer = $this->consumerService->getGenericConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\GenericConsumer::class, $consumer);
    }

    public function testGetQuotedStringConsumer()
    {
        $consumer = $this->consumerService->getQuotedStringConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer::class, $consumer);
    }

    public function testGetDateConsumer()
    {
        $consumer = $this->consumerService->getDateConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\DateConsumer::class, $consumer);
    }

    public function testGetParameterConsumer()
    {
        $consumer = $this->consumerService->getParameterConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\ParameterConsumer::class, $consumer);
    }
}
