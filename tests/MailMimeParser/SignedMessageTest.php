<?php
namespace ZBateson\MailMimeParser;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Psr7;
use org\bovigo\vfs\vfsStream;

/**
 * Description of MessageTest
 *
 * @group SignedMessage
 * @group Base
 * @covers ZBateson\MailMimeParser\SignedMessage
 * @author Zaahid Bateson
 */
class SignedMessageTest extends PHPUnit_Framework_TestCase
{
    private $mockPartStreamFilterManager;
    private $mockHeaderFactory;
    private $mockPartFilterFactory;
    private $vfs;

    protected function setUp()
    {
        $this->vfs = vfsStream::setup('root');
        $this->mockPartStreamFilterManager = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartFilterFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilterFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }
    
    protected function getMockedParameterHeader($name, $value, $parameterValue = null)
    {
        $header = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'getName', 'getValueFor', 'hasParameter'])
            ->getMock();
        $header->method('getName')->willReturn($name);
        $header->method('getValue')->willReturn($value);
        $header->method('getValueFor')->willReturn($parameterValue);
        $header->method('hasParameter')->willReturn(true);
        return $header;
    }
    
    protected function getMockedPartBuilder()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }
    
    protected function getMockedPartBuilderWithChildren()
    {
        $pb = $this->getMockedPartBuilder();
        $children = [
            $this->getMockedPartBuilder(),
            $this->getMockedPartBuilder(),
            $this->getMockedPartBuilder()
        ];
        
        $nestedMimePart = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->getMock();
        $nestedMimePart
            ->method('getMessageObjectId')
            ->willReturn('nested');
        
        $nested = $this->getMockedPartBuilder();
        $nested->method('createMessagePart')
            ->willReturn($nestedMimePart);
        $children[0]->method('getChildren')
            ->willReturn([$nested]);
        
        foreach ($children as $key => $child) {
            // need to 'setMethods' because getAllNonFilteredParts is protected
            $childMimePart = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->setMethods([
                'getMessageObjectId',
                'getAllNonFilteredParts',
                '__destruct',
                'getContentResourceHandle',
                'getContent',
                'getHandle',
                'isTextPart',
                'getHeaderValue'
            ])
            ->getMock();
            $childMimePart->
                method('getMessageObjectId')
                ->willReturn('child' . $key);
            
            if ($key === 0) {
                $childMimePart->expects($this->any())
                    ->method('getAllNonFilteredParts')
                    ->willReturn([$childMimePart, $nestedMimePart]);
            } else {
                $childMimePart
                    ->method('getAllNonFilteredParts')
                    ->willReturn([$childMimePart]);
            }
            
            $child->method('createMessagePart')
                ->willReturn($childMimePart);
        }
        $pb->method('getChildren')
            ->willReturn($children);
        return $pb;
    }
    
    public function testInstance()
    {
        $message = new SignedMessage(
            $this->mockHeaderFactory,
            $this->mockPartFilterFactory,
            $this->getMockedPartBuilder(),
            $this->mockPartStreamFilterManager,
            Psr7\stream_for('habibis'),
            Psr7\stream_for('7ajat 7ilwa')
        );
        $this->assertNotNull($message);
        $this->assertInstanceOf('ZBateson\MailMimeParser\SignedMessage', $message);
    }
    
    public function testGetSignedMessageAsStringWithoutChildren()
    {
        $message = new SignedMessage(
            $this->mockHeaderFactory,
            $this->mockPartFilterFactory,
            $this->getMockedPartBuilder(),
            $this->mockPartStreamFilterManager,
            Psr7\stream_for('habibis'),
            Psr7\stream_for('7ajat 7ilwa')
        );
        $this->assertNull($message->getSignedMessageAsString());
    }
    
    public function testGetSignedMessageAsString()
    {
        $message = new SignedMessage(
            $this->mockHeaderFactory,
            $this->mockPartFilterFactory,
            $this->getMockedPartBuilderWithChildren(),
            $this->mockPartStreamFilterManager,
            Psr7\stream_for('habibis'),
            Psr7\stream_for('7ajat 7ilwa')
        );
        $content = vfsStream::newFile('part')->at($this->vfs);
        $content->withContent("mucha\ragua\ny\r\npollo\r\n\r\n");
        $handle = fopen($content->url(), 'r');
        
        $child = $message->getChild(0);
        $child->method('getHandle')
            ->willReturn($handle);
        
        $this->assertEquals("mucha\r\nagua\r\ny\r\npollo\r\n\r\n", $message->getSignedMessageAsString());
        fclose($handle);
    }
}
