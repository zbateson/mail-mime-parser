<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

/**
 * MessagePartFactoryTest
 * 
 * @group MessagePartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\MessagePartFactory
 * @author Zaahid Bateson
 */
class MessagePartFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $messagePartFactory;
    
    protected function setUp()
    {
        $this->messagePartFactory = $this->getMockBuilder(
            'ZBateson\MailMimeParser\Message\Part\MessagePartFactory'
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }
    
    public function testNewInstance()
    {
        $messageId = 'the id';
        $partBuilder = $this->getMockBuilder(
            'ZBateson\MailMimeParser\Message\Part\PartBuilder'
        )
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->messagePartFactory->expects($this->once())
            ->method('newInstance')
            ->with($messageId, $partBuilder);
        
        $this->messagePartFactory->newInstance($messageId, $partBuilder);
    }
}
