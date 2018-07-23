<?php
namespace ZBateson\MailMimeParser\Message;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Psr7;

/**
 * MessageFactoryTest
 * 
 * @group MessageFactory
 * @group Message
 * @covers ZBateson\MailMimeParser\Message\MessageFactory
 * @author Zaahid Bateson
 */
class MessageFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $messageFactory;
    
    protected function setUp()
    {
        $mocksdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->getMock();
        $mockpsfm = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockpsfmfactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\PartStreamFilterManagerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
        $mockFilterFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilterFactory')
            ->disableOriginalConstructor()
            ->getMock();
        
        $mockpsfmfactory->method('newInstance')
            ->willReturn($mockpsfm);
        
        $this->messageFactory = new MessageFactory(
            $mocksdf,
            $mockpsfmfactory,
            $mockHeaderFactory,
            $mockFilterFactory
        );
    }
    
    public function testNewInstance()
    {
        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        
        $part = $this->messageFactory->newInstance(
            Psr7\stream_for('test'),
            $partBuilder
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message',
            $part
        );
    }
}
