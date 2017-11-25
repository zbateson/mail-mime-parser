<?php
namespace ZBateson\MailMimeParser\Message;

use PHPUnit_Framework_TestCase;

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
        $mockpsfm = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockpsfmfactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManagerFactory')
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
            $mockpsfmfactory,
            $mockHeaderFactory,
            $mockFilterFactory
        );
    }
    
    public function testNewInstance()
    {
        $messageId = 'the id';
        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        
        $part = $this->messageFactory->newInstance(
            $messageId,
            $partBuilder
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message',
            $part
        );
    }
}
