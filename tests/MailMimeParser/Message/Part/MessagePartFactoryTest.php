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
        $handle = fopen('php://memory', 'r');
        $ch = fopen('php://memory', 'r');
        $children = [];
        $headers = ['Content-Type' => 'test/test'];
        $properties = ['name' => 'value'];
        
        $this->messagePartFactory->expects($this->once())
            ->method('newInstance')
            ->with(
                $handle,
                $ch,
                $children,
                $headers,
                $properties
            );
        
        $this->messagePartFactory->newInstance(
            $handle,
            $ch,
            $children,
            $headers,
            $properties
        );
    }
}
