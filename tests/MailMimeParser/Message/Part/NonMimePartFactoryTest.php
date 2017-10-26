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
        $handle = fopen('php://memory', 'r');
        $cHandle = fopen('php://memory', 'r');
        $children = [];
        $headers = ['Content-Type' => 'test/test'];
        $properties = ['name' => 'value'];
        
        $part = $this->nonMimePartFactory->newInstance(
            $handle,
            $cHandle,
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
