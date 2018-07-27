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
    private $mockMessagePartFactory;

    protected function setUp()
    {
        $this->mockMessagePartFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\MessagePartFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
    }

    private function newMockHeaderContainer()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderContainer')
            ->disableOriginalConstructor()
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
        
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $this->newMockHeaderContainer()
        );
        
        $this->assertTrue($instance->canHaveHeaders());

        $hc = $this->newMockHeaderContainer();
        $parent = new PartBuilder(
            $this->mockMessagePartFactory,
            $hc
        );
        $hc->expects($this->once())
            ->method('add')
            ->with('CONTENT-TYPE', 'kookoo-keekee');
        $hc->expects($this->once())
            ->method('get')
            ->with('Content-Type')
            ->willReturn($mockHeader);
        $parent->addHeader('CONTENT-TYPE', 'kookoo-keekee');
        $parent->addChild($instance);
        
        $parent->setEndBoundaryFound('--Castle Black--');
        $this->assertFalse($instance->canHaveHeaders());
    }

    public function testAddChildren()
    {
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $this->newMockHeaderContainer()
        );
        $children = [
            new PartBuilder(
                $this->mockMessagePartFactory,
                $this->newMockHeaderContainer()
            ),
            new PartBuilder(
                $this->mockMessagePartFactory,
                $this->newMockHeaderContainer()
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
        $hc = $this->newMockHeaderContainer();
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $hc
        );
        $hc->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                [ 'Mime-VERSION', '42' ],
                [ 'Content-TYPE', 'text/blah; blooh' ],
                [ 'X-Northernmost-Castle', 'Castle black' ]
            );
        $instance->addHeader('Mime-VERSION', '42');
        $instance->addHeader('Content-TYPE', 'text/blah; blooh');
        $instance->addHeader('X-Northernmost-Castle', 'Castle black');

        $this->assertSame($hc, $instance->getHeaderContainer());
    }
    
    public function testIsMime()
    {
        $hc = $this->newMockHeaderContainer();
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $hc
        );
        $hc->expects($this->exactly(5))
            ->method('exists')
            ->withConsecutive(
                [ 'Content-Type', 0 ],
                [ 'Content-Type', 0 ],
                [ 'Mime-Version', 0 ],
                [ 'Content-Type', 0 ],
                [ 'Mime-Version', 0 ]
            )
            ->willReturnOnConsecutiveCalls(true, false, true, false, false);

        $this->assertTrue($instance->isMime());
        $this->assertTrue($instance->isMime());
        $this->assertFalse($instance->isMime());
    }
    
    public function testGetContentType()
    {
        $hc = $this->newMockHeaderContainer();
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $hc
        );
        $hc->expects($this->once())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn(true);
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

        $hc = $this->newMockHeaderContainer();
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $hc
        );
        $hc->expects($this->once())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($mockHeader);
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

        $hc = $this->newMockHeaderContainer();
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $hc
        );
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($mockHeader);

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
        
        $hc = $this->newMockHeaderContainer();
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $hc
        );
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($mockHeader);
        
        $this->assertFalse($instance->isParentBoundaryFound());
        $this->assertFalse($instance->setEndBoundaryFound('Somewhere... obvs not Castle Black'));
        $this->assertFalse($instance->setEndBoundaryFound('Castle Black'));
        $this->assertTrue($instance->setEndBoundaryFound('--Castle Black'));
        $this->assertFalse($instance->isParentBoundaryFound());
        $this->assertTrue($instance->setEndBoundaryFound('--Castle Black--'));
        
        $child = new PartBuilder(
            $this->mockMessagePartFactory,
            $this->newMockHeaderContainer()
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

        $hc = $this->newMockHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($mockHeader);
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $hc
        );

        $hcp = $this->newMockHeaderContainer();
        $hcp->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($mockParentHeader);
        $parent = new PartBuilder(
            $this->mockMessagePartFactory,
            $hcp
        );
        $parent->addChild($instance);
        
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
        
        $hc = $this->newMockHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($mockHeader);
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $hc
        );

        $hcp = $this->newMockHeaderContainer();
        $hcp->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($mockParentHeader);
        $parent = new PartBuilder(
            $this->mockMessagePartFactory,
            $hcp
        );
        $parent->addChild($instance);
        
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
            $this->mockMessagePartFactory,
            $this->newMockHeaderContainer()
        );
        $instance->setStreamPartStartPos(42);
        $instance->setStreamPartEndPos(84);
        $this->assertEquals(42, $instance->getStreamPartStartOffset());
        $this->assertEquals(42, $instance->getStreamPartLength());
    }
    
    public function testSetStreamContentPosAndGetFilename()
    {
        $instance = new PartBuilder(
            $this->mockMessagePartFactory,
            $this->newMockHeaderContainer()
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
            $this->mockMessagePartFactory,
            $this->newMockHeaderContainer()
        );
        $parent = new PartBuilder(
            $this->mockMessagePartFactory,
            $this->newMockHeaderContainer()
        );
        $super = new PartBuilder(
            $this->mockMessagePartFactory,
            $this->newMockHeaderContainer()
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
            $this->mockMessagePartFactory,
            $this->newMockHeaderContainer()
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
            $this->mockMessagePartFactory,
            $this->newMockHeaderContainer()
        );

        $this->mockMessagePartFactory->expects($this->once())
            ->method('newInstance')
            ->with($instance, $stream)
            ->willReturn(true);
        $this->assertTrue($instance->createMessagePart($stream));
    }
}
