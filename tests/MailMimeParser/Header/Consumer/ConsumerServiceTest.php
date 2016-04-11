<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of ConsumerServiceTest
 *
 * @group Consumers
 * @group ConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\ConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class ConsumerServiceTest extends PHPUnit_Framework_TestCase
{
    private $consumerService;
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $this->consumerService = new ConsumerService($pf, $mlpf);
    }
    
    public function testGetAddressBaseConsumer()
    {
        $consumer = $this->consumerService->getAddressBaseConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Consumer\AddressBaseConsumer', $consumer);
    }
    
    public function testGetAddressConsumer()
    {
        $consumer = $this->consumerService->getAddressConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Consumer\AddressConsumer', $consumer);
    }
    
    public function testGetAddressGroupConsumer()
    {
        $consumer = $this->consumerService->getAddressGroupConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Consumer\AddressGroupConsumer', $consumer);
    }
    
    public function testGetCommentConsumer()
    {
        $consumer = $this->consumerService->getCommentConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Consumer\CommentConsumer', $consumer);
    }
    
    public function testGetGenericConsumer()
    {
        $consumer = $this->consumerService->getGenericConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Consumer\GenericConsumer', $consumer);
    }
    
    public function testGetQuotedStringConsumer()
    {
        $consumer = $this->consumerService->getQuotedStringConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer', $consumer);
    }
    
    public function testGetDateConsumer()
    {
        $consumer = $this->consumerService->getDateConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Consumer\DateConsumer', $consumer);
    }
    
    public function testGetParameterConsumer()
    {
        $consumer = $this->consumerService->getParameterConsumer();
        $this->assertNotNull($consumer);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Consumer\ParameterConsumer', $consumer);
    }
}
