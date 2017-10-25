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
        $this->uuEncodedPartFactory = new UUEncodedPartFactory();
    }
    
    public function testNewInstance()
    {
        $handle = fopen('php://memory', 'r');
        $cHandle = fopen('php://memory', 'r');
        $children = [];
        $headers = ['Content-Type' => 'test/test'];
        $properties = ['name' => 'value'];
        
        $part = $this->uuEncodedPartFactory->newInstance(
            $handle,
            $cHandle,
            $children,
            $headers,
            $properties
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\Part\UUEncodedPart',
            $part
        );
    }
}
