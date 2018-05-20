<?php
namespace ZBateson\MailMimeParser\Message\Part\Factory;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Psr7;

/**
 * NonMimePartFactoryTest
 * 
 * @group NonMimePartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\Factory\NonMimePartFactory
 * @covers ZBateson\MailMimeParser\Message\Part\Factory\MessagePartFactory
 * @author Zaahid Bateson
 */
class NonMimePartFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $nonMimePartFactory;
    
    protected function setUp()
    {
        $mocksdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamDecoratorFactory')
            ->getMock();
        $mocksdf->expects($this->any())
            ->method('getLimitedPartStream')
            ->willReturn(Psr7\stream_for('test'));
        $psfmFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\PartStreamFilterManagerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $psfm = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $psfmFactory
            ->method('newInstance')
            ->willReturn($psfm);
        
        $this->nonMimePartFactory = new NonMimePartFactory($mocksdf, $psfmFactory);
    }
    
    public function testNewInstance()
    {
        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        
        $part = $this->nonMimePartFactory->newInstance(
            Psr7\stream_for('test'),
            $partBuilder
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\Part\NonMimePart',
            $part
        );
    }
}
