<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

/**
 * NonMimePartFactoryTest
 * 
 * @group NonMimePartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\NonMimePartFactory
 * @author Zaahid Bateson
 */
class NonMimePartFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $nonMimePartFactory;
    
    protected function setUp()
    {
        $this->nonMimePartFactory = NonMimePartFactory::getInstance();
    }
    
    public function testNewInstance()
    {
        $messageId = 'the id';
        $partBuilder = $this->getMockBuilder(
            'ZBateson\MailMimeParser\Message\Part\PartBuilder'
        )
            ->disableOriginalConstructor()
            ->getMock();
        
        $part = $this->nonMimePartFactory->newInstance(
            $messageId,
            $partBuilder
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\Part\NonMimePart',
            $part
        );
    }
}
