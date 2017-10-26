<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of PartBuilder
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
        $this->mockMessagePartFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MessagePartFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }
    
    public function testSetAndGetParent()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory
        );
        $parent = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory
        );
        $instance->setParent($parent);
        $this->assertSame($parent, $instance->getParent());
        $this->assertNull($parent->getParent());
    }
    
    public function testAddChildren()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory
        );
        $children = [
            new PartBuilder(
                $this->mockHeaderFactory,
                $this->mockMessagePartFactory
            ),
            new PartBuilder(
                $this->mockHeaderFactory,
                $this->mockMessagePartFactory
            )
        ];
        foreach ($children as $child) {
            $instance->addChild($child);
        }
        $this->assertEquals($children, $instance->getChildren());
        $this->assertSame($instance, $children[0]->getParent());
        $this->assertSame($instance, $children[1]->getParent());
    }
    
    public function testAddMimeVersionHeader()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory
        );
        $instance->addHeader('Mime-VERSION', '42');
        $this->assertTrue($instance->isMime());
    }
    
    public function testAddContentTypeHeaderIsMime()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory
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
            $this->mockMessagePartFactory
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
            $this->mockMessagePartFactory
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
            $this->mockMessagePartFactory
        );
        $instance->addHeader('CONTENT-TYPE', 'kookoo-keekee');
        $this->assertTrue($instance->isMultiPart());
        $this->assertFalse($instance->isMultiPart());
    }
    
    public function testSetEndBoundary()
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
            ->willReturnOnConsecutiveCalls($mockHeader);
        
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory
        );
        
        $instance->addHeader('CONTENT-TYPE', 'kookoo-keekee');
        
        $this->assertFalse($instance->isEndBoundaryFound());
        $this->assertFalse($instance->setEndBoundary('Somewhere... obvs not Castle Black'));
        $this->assertFalse($instance->setEndBoundary('Castle Black'));
        $this->assertTrue($instance->setEndBoundary('--Castle Black'));
        $this->assertFalse($instance->isEndBoundaryFound());
        $this->assertTrue($instance->setEndBoundary('--Castle Black--'));
        $this->assertTrue($instance->isEndBoundaryFound());
    }
    
    public function testSetEndBoundaryWithParent()
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
            $this->mockMessagePartFactory
        );
        $parent = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory
        );
        $parent->addChild($instance);
        
        $instance->addHeader('CONTENT-TYPE', 'kookoo-keekee');
        $parent->addHeader('CONTENT-TYPE', 'keekee-kookoo');
        
        $this->assertSame($parent, $instance->getParent());
        $this->assertFalse($instance->isEndBoundaryFound());
        $this->assertFalse($instance->setEndBoundary('Somewhere... obvs not Castle Black'));
        $this->assertFalse($instance->setEndBoundary('King\'s Landing'));
        $this->assertTrue($instance->setEndBoundary('--King\'s Landing'));
        $this->assertFalse($instance->isEndBoundaryFound());
        $this->assertFalse($parent->isEndBoundaryFound());
        $this->assertTrue($instance->setEndBoundary('--King\'s Landing--'));
        $this->assertTrue($parent->isEndBoundaryFound());
        $this->assertFalse($instance->isEndBoundaryFound());
    }
    
    public function testSetAndGetStreamStartAndEndPos()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory
        );
        $instance->setStreamPartStartPos(42);
        $instance->setStreamPartEndPos(84);
        $this->assertEquals(42, $instance->getStreamPartStartPos());
        $this->assertEquals(84, $instance->getStreamPartEndPos());
    }
    
    public function testSetAndGetContentStartAndEndPos()
    {
        $instance = new PartBuilder(
            $this->mockHeaderFactory,
            $this->mockMessagePartFactory
        );
        $instance->setStreamContentStartPos(42);
        $instance->setStreamContentEndPos(84);
        $this->assertEquals(42, $instance->getStreamContentStartPos());
        $this->assertEquals(84, $instance->getStreamContentEndPos());
    }
}
