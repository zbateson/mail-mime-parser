<?php
namespace ZBateson\MailMimeParser\Message\Part\Factory;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Psr7;

/**
 * MessagePartFactoryTest
 * 
 * @group MessagePartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\Factory\MessagePartFactory
 * @author Zaahid Bateson
 */
class MessagePartFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $messagePartFactory;
    
    protected function setUp()
    {
        $this->messagePartFactory = $this->getMockBuilder(
            'ZBateson\MailMimeParser\Message\Part\Factory\MessagePartFactory'
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }
    
    public function testNewInstance()
    {
        $partBuilder = $this->getMockBuilder(
            'ZBateson\MailMimeParser\Message\Part\PartBuilder'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $stream = Psr7\stream_for('stuff');
        $this->messagePartFactory->expects($this->once())
            ->method('newInstance')
            ->with($stream, $partBuilder);
        
        $this->messagePartFactory->newInstance($stream, $partBuilder);
    }
}
