<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

/**
 * MimePartFactoryTest
 * 
 * @group MimePartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\MimePartFactory
 * @author Zaahid Bateson
 */
class MimePartFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $mimePartFactory;
    
    protected function setUp()
    {
        $mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
        $this->mimePartFactory = new MimePartFactory($mockHeaderFactory);
    }
    
    public function testNewInstance()
    {
        $handle = fopen('php://memory', 'r');
        $cHandle = fopen('php://memory', 'r');
        $children = [];
        $headers = [];
        $properties = [];
        
        $part = $this->mimePartFactory->newInstance(
            $handle,
            $cHandle,
            $children,
            $headers,
            $properties
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\Part\MimePart',
            $part
        );
    }
}
