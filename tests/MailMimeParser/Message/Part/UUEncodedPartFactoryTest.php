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
        $handle = 'handle';
        $mp = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->getMock();
        $children = ['children'];
        $headers = ['headers'];
        $properties = ['properties'];
        
        $part = $this->uuEncodedPartFactory->newInstance(
            $handle,
            $mp,
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
