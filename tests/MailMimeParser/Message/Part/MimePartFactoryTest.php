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
    protected $partFilterFactory;
    
    protected function setUp()
    {
        $mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
        $mockFilterFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilterFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mimePartFactory = MimePartFactory::getInstance($mockHeaderFactory, $mockFilterFactory);
    }
    
    public function testNewInstance()
    {
        $messageId = 'the id';
        $partBuilder = $this->getMockBuilder(
            'ZBateson\MailMimeParser\Message\Part\PartBuilder'
        )
            ->disableOriginalConstructor()
            ->getMock();
        
        $part = $this->mimePartFactory->newInstance(
            $messageId,
            $partBuilder
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\Part\MimePart',
            $part
        );
    }
}
