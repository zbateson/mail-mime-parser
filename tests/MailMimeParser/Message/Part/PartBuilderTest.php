<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Psr7;

/**
 * PartBuilderTest
 *
 * @group PartBuilder
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\PartBuilder
 * @author Zaahid Bateson
 */
class PartBuilderTest extends PHPUnit_Framework_TestCase
{
    private $mockHeaderFactory;
    private $mockMessagePartFactory;
    
    protected function setUp()
    {
        $this->mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
        $this->mockMessagePartFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\MessagePartFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
    }
    
    public function testCanHaveHeaders()
    {
        $mockHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValueFor'])
            ->getMock();
        $mockHeader->expects($this->any())
            ->method('getValueFor')
            ->with('boundary')
            ->willReturn('Castle Black');
        
        $this->mockHeaderFactory
            ->expects($this->any())
            ->method('newInstance')
            ->willReturn($mockHeader);
        
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        
        $this->assertTrue($instance->canHaveHeaders());
        
        $parent = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $parent->addHeader('CONTENT-TYPE', 'kookoo-keekee');
        $parent->addChild($instance);
        
        $parent->setEndBoundaryFound('--Castle Black--');
        $this->assertFalse($instance->canHaveHeaders());
    }

    public function testAddChildren()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $children = [
            new PartBuilder(
                $this->mockHeaderFactory,
                $this->mockMessagePartFactory,
                'euphrates'
            ),
            new PartBuilder(
                $this->mockHeaderFactory,
                $this->mockMessagePartFactory,
                'euphrates'
            )
        ];
        foreach ($children as $child) {
            $instance->addChild($child);
        }
        $this->assertEquals($children, $instance->getChildren());
        $this->assertSame($instance, $children[0]->getParent());
        $this->assertSame($instance, $children[1]->getParent());
    }
    
    public function testAddAndGetRawHeaders()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $instance->addHeader('Mime-VERSION', '42');
        $instance->addHeader('Content-TYPE', 'text/blah; blooh');
        $instance->addHeader('X-Northernmost-Castle', 'Castle black');
        
        $expectedHeaders = [
            'mimeversion' => ['Mime-VERSION', '42'],
            'contenttype' => ['Content-TYPE', 'text/blah; blooh'],
            'xnorthernmostcastle' => ['X-Northernmost-Castle', 'Castle black']
        ];
        $this->assertEquals($expectedHeaders, $instance->getRawHeaders());
    }
    
    public function testAddMimeVersionHeader()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $instance->addHeader('Mime-VERSION', '42');
        $this->assertTrue($instance->isMime());
    }
    
    public function testAddContentTypeHeaderIsMime()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $instance->addHeader('CONTENT-TYPE', '42');
        $this->assertTrue($instance->isMime());
    }
    
    public function testGetContentType()
    {
        $this->mockHeaderFactory
            ->expects($this->atLeastOnce())
            ->method('newInstance')
            ->willReturn(true);
        
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $instance->addHeader('CONTENT-TYPE', '42');
        $this->assertTrue($instance->getContentType());
    }
    
    public function testGetMimeBoundary()
    {
        $mockHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValueFor'])
            ->getMock();
        $mockHeader->expects($this->any())
            ->method('getValueFor')
            ->with('boundary')
            ->willReturn('Castle Black');
        
        $this->mockHeaderFactory
            ->expects($this->atLeastOnce())
            ->method('newInstance')
            ->willReturn($mockHeader);
        
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $instance->addHeader('CONTENT-TYPE', 'Snow and Ice');
        $this->assertEquals('Castle Black', $instance->getMimeBoundary());
    }
    
    public function testIsMultiPart()
    {
        $mockHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $mockHeader->expects($this->any())
            ->method('getValue')
            ->willReturnOnConsecutiveCalls('multipart/kookoo', 'text/plain');
        
        $this->mockHeaderFactory
            ->expects($this->atLeastOnce())
            ->method('newInstance')
            ->willReturn($mockHeader);
        
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $instance->addHeader('CONTENT-TYPE', 'kookoo-keekee');
        $this->assertTrue($instance->isMultiPart());
        $this->assertFalse($instance->isMultiPart());
    }
    
    public function testSetEndBoundaryFound()
    {
        $mockHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValueFor'])
            ->getMock();
        $mockHeader->expects($this->any())
            ->method('getValueFor')
            ->with('boundary')
            ->willReturn('Castle Black');
        
        $this->mockHeaderFactory
            ->expects($this->any())
            ->method('newInstance')
            ->willReturnOnConsecutiveCalls($mockHeader);
        
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        
        $instance->addHeader('CONTENT-TYPE', 'kookoo-keekee');
        
        $this->assertFalse($instance->isParentBoundaryFound());
        $this->assertFalse($instance->setEndBoundaryFound('Somewhere... obvs not Castle Black'));
        $this->assertFalse($instance->setEndBoundaryFound('Castle Black'));
        $this->assertTrue($instance->setEndBoundaryFound('--Castle Black'));
        $this->assertFalse($instance->isParentBoundaryFound());
        $this->assertTrue($instance->setEndBoundaryFound('--Castle Black--'));
        
        $child = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'tigris'
        );
        $instance->addChild($child);
        $this->assertEquals($instance, $child->getParent());
        $this->assertCount(0, $instance->getChildren());
        $this->assertFalse($child->canHaveHeaders());
    }
    
    public function testSetEndBoundaryFoundWithParent()
    {
        $mockParentHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValueFor'])
            ->getMock();
        $mockParentHeader->expects($this->any())
            ->method('getValueFor')
            ->with('boundary')
            ->willReturn('King\'s Landing');
        
        $mockHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValueFor'])
            ->getMock();
        $mockHeader->expects($this->any())
            ->method('getValueFor')
            ->with('boundary')
            ->willReturn(null);
        
        $this->mockHeaderFactory
            ->expects($this->atLeastOnce())
            ->method('newInstance')
            ->willReturnOnConsecutiveCalls($mockHeader, $mockParentHeader);
        
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $parent = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $parent->addChild($instance);
        
        $instance->addHeader('CONTENT-TYPE', 'kookoo-keekee');
        $parent->addHeader('CONTENT-TYPE', 'keekee-kookoo');
        
        $this->assertSame($parent, $instance->getParent());
        $this->assertFalse($instance->isParentBoundaryFound());
        $this->assertFalse($instance->setEndBoundaryFound('Somewhere... obvs not Castle Black'));
        $this->assertFalse($instance->setEndBoundaryFound('King\'s Landing'));
        $this->assertTrue($instance->setEndBoundaryFound('--King\'s Landing'));
        $this->assertTrue($instance->isParentBoundaryFound());
    }
    
    public function testSetEof()
    {
        $mockParentHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValueFor'])
            ->getMock();
        $mockParentHeader->expects($this->any())
            ->method('getValueFor')
            ->with('boundary')
            ->willReturn('King\'s Landing');
        
        $mockHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValueFor'])
            ->getMock();
        $mockHeader->expects($this->any())
            ->method('getValueFor')
            ->with('boundary')
            ->willReturn(null);
        
        $this->mockHeaderFactory
            ->expects($this->atLeastOnce())
            ->method('newInstance')
            ->willReturnOnConsecutiveCalls($mockHeader, $mockParentHeader);
        
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $parent = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $parent->addChild($instance);
        
        $instance->addHeader('CONTENT-TYPE', 'kookoo-keekee');
        $parent->addHeader('CONTENT-TYPE', 'keekee-kookoo');
        
        $this->assertSame($parent, $instance->getParent());
        $this->assertFalse($instance->isParentBoundaryFound());
        $this->assertFalse($instance->setEndBoundaryFound('Somewhere... obvs not Castle Black'));
        $this->assertFalse($instance->setEndBoundaryFound('Szprotka'));
        $this->assertFalse($instance->setEndBoundaryFound('--szprotka'));
        $this->assertFalse($instance->isParentBoundaryFound());
        $instance->setEof();
        $this->assertTrue($instance->isParentBoundaryFound());
        $this->assertTrue($parent->isParentBoundaryFound());
    }
    
    public function testSetStreamPartPosAndGetFilename()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $instance->setStreamPartStartPos(42);
        $instance->setStreamPartEndPos(84);
        $this->assertEquals(42, $instance->getStreamPartStartOffset());
        $this->assertEquals(42, $instance->getStreamPartLength());
    }
    
    public function testSetStreamContentPosAndGetFilename()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'tigris'
        );
        $instance->setStreamPartStartPos(11);
        $instance->setStreamContentStartPos(42);
        $instance->setStreamPartAndContentEndPos(84);
        $this->assertEquals(11, $instance->getStreamPartStartOffset());
        $this->assertEquals(84 - 11, $instance->getStreamPartLength());
        $this->assertEquals(42, $instance->getStreamContentStartOffset());
        $this->assertEquals(84 - 42, $instance->getStreamContentLength());
    }
    
    public function testSetStreamContentPosAndGetFilenameWithParent()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'tigris'
        );
        $parent = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $super = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'vistula'
        );
        $parent->addChild($instance);
        $super->addChild($parent);
        
        $super->setStreamPartStartPos(0);
        $super->setStreamContentStartPos(3);
        $super->setStreamPartAndContentEndPos(3);
        
        $parent->setStreamPartStartPos(11);
        $parent->setStreamContentStartPos(13);
        $parent->setStreamPartAndContentEndPos(20);
        
        $instance->setStreamPartStartPos(22);
        $instance->setStreamContentStartPos(42);
        $instance->setStreamPartAndContentEndPos(84);

        $this->assertEquals(42 - $parent->getStreamPartStartOffset(), $instance->getStreamContentStartOffset());
        $this->assertEquals(84 - 42, $instance->getStreamContentLength());
        $this->assertEquals(22 - $parent->getStreamPartStartOffset(), $instance->getStreamPartStartOffset());
        $this->assertEquals(84 - 22, $instance->getStreamPartLength());

        $this->assertEquals(13, $parent->getStreamContentStartOffset());
        $this->assertEquals(20 - 13, $parent->getStreamContentLength());
        $this->assertEquals(11, $parent->getStreamPartStartOffset());
        $this->assertEquals(84 - 11, $parent->getStreamPartLength());

        $this->assertEquals(3, $super->getStreamContentStartOffset());
        $this->assertEquals(0, $super->getStreamContentLength());
        $this->assertEquals(0, $super->getStreamPartStartOffset());
        $this->assertEquals(84, $super->getStreamPartLength());
    }
    
    public function testSetAndGetProperties()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );
        $instance->setProperty('island', 'Westeros');
        $instance->setProperty('capital', 'King\'s Landing');
        $this->assertSame('Westeros', $instance->getProperty('island'));
        $this->assertSame('King\'s Landing', $instance->getProperty('capital'));
        $this->assertNull($instance->getProperty('Joffrey\'s kindness'));
    }
    
    public function testCreateMessagePart()
    {
        $stream = Psr7\stream_for('thingsnstuff');
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory,
            'euphrates'
        );

        $this->mockMessagePartFactory->expects($this->once())
            ->method('newInstance')
            ->with($stream)
            ->willReturn(true);
        $this->assertTrue($instance->createMessagePart($stream));
    }
}
