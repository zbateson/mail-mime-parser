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
        $mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
        $this->nonMimePartFactory = new NonMimePartFactory($mockHeaderFactory);
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
        
        $part = $this->nonMimePartFactory->newInstance(
            $handle,
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
