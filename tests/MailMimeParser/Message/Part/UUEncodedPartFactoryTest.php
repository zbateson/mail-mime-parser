<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

/**
 * UUEncodedPartFactoryTest
 * 
 * @group UUEncodedPartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\UUEncodedPartFactory
 * @author Zaahid Bateson
 */
class UUEncodedPartFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $uuEncodedPartFactory;
    
    protected function setUp()
    {
        $this->uuEncodedPartFactory = UUEncodedPartFactory::getInstance();
    }
    
    public function testNewInstance()
    {
        $messageId = 'the id';
        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        
        $part = $this->uuEncodedPartFactory->newInstance(
            $messageId,
            $partBuilder
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\Part\UUEncodedPart',
            $part
        );
    }
}
