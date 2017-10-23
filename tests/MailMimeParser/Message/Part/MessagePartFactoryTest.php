<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

/**
 * MessagePartFactoryTest
 * 
 * @group MessagePartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\MessagePart
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
        $handle = 'handle';
        $mp = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->getMock();
        $children = ['children'];
        $headers = ['headers'];
        $properties = ['properties'];
        
        $this->messagePartFactory->expects($this->once())
            ->method('newInstance')
            ->with(
                $handle,
                $mp,
                $children,
                $headers,
                $properties
            );
        
        $this->messagePartFactory->newInstance(
            $handle,
            $mp,
            $children,
            $headers,
            $properties
        );
    }
}
