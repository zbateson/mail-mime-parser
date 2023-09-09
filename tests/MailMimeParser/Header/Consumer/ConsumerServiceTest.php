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
    // @phpstan-ignore-next-line
    private $consumerService;

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
        $this->consumerService = new ConsumerService($pf, $mlpf);
    }

    public function testIsService() : void
    {
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Container\IService::class, $this->consumerService);
    }

    public function testGetAddressBaseConsumer() : void
    {
        $consumer = $this->consumerService->getAddressBaseConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\AddressBaseConsumerService::class, $consumer);
    }

    public function testGetAddressConsumer() : void
    {
        $consumer = $this->consumerService->getAddressConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\AddressConsumerService::class, $consumer);
    }

    public function testGetAddressEmailConsumer() : void
    {
        $consumer = $this->consumerService->getAddressEmailConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\AddressEmailConsumerService::class, $consumer);
    }

    public function testGetAddressGroupConsumer() : void
    {
        $consumer = $this->consumerService->getAddressGroupConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\AddressGroupConsumerService::class, $consumer);
    }

    public function testGetCommentConsumer() : void
    {
        $consumer = $this->consumerService->getCommentConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService::class, $consumer);
    }

    public function testGetGenericConsumer() : void
    {
        $consumer = $this->consumerService->getGenericConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\GenericConsumerService::class, $consumer);
    }

    public function testGetQuotedStringConsumer() : void
    {
        $consumer = $this->consumerService->getQuotedStringConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService::class, $consumer);
    }

    public function testGetDateConsumer() : void
    {
        $consumer = $this->consumerService->getDateConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\DateConsumerService::class, $consumer);
    }

    public function testGetParameterConsumer() : void
    {
        $consumer = $this->consumerService->getParameterConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Consumer\ParameterConsumerService::class, $consumer);
    }
}
