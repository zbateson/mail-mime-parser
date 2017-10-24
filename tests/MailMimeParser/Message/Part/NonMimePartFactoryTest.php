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
        $this->nonMimePartFactory = new NonMimePartFactory();
    }
    
    public function testNewInstance()
    {
        $handle = fopen('php://memory', 'r');
        $cHandle = fopen('php://memory', 'r');
        $mp = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->getMock();
        $children = ['children'];
        $headers = ['headers'];
        $properties = ['properties'];
        
        $part = $this->nonMimePartFactory->newInstance(
            $handle,
            $cHandle,
            $mp,
            $children,
            $headers,
            $properties
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\Part\NonMimePart',
            $part
        );
    }
}
