<?php
namespace ZBateson\MailMimeParser\Parser;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

/**
 * PartBuilderTest
 *
 * @group PartBuilder
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\PartBuilder
 * @author Zaahid Bateson
 */
class PartBuilderTest extends TestCase
{
    private $parsedMessagePartFactory;
    private $streamFactory;
    private $baseParser;

    protected function legacySetUp()
    {
        $this->parsedMessagePartFactory = $this->getMockForAbstractClass(
            'ZBateson\MailMimeParser\Parser\Part\ParsedMessagePartFactory',
            [],
            '',
            false
        );
        $this->streamFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->baseParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\BaseParser')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newMockHeaderContainer()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newPartBuilder($headerContainer = null, $stream = null, $parent = null)
    {
        if ($stream === null && $parent === null) {
            $stream = Psr7\stream_for('test');
        }
        return new PartBuilder(
            $this->parsedMessagePartFactory,
            $this->streamFactory,
            $this->baseParser,
            ($headerContainer === null) ? $this->newMockHeaderContainer() : $headerContainer,
            $stream,
            $parent
        );
    }

    private function setContainers($partBuilder, $streamContainer = null, $childrenContainer = null)
    {
        if ($streamContainer === null) {
            $streamContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParsedPartStreamContainer')
                ->disableOriginalConstructor()
                ->getMock();
        }
        if ($childrenContainer === null) {
            $childrenContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParsedPartChildrenContainer')
                ->disableOriginalConstructor()
                ->getMock();
        }
        $partBuilder->setContainers(
            $streamContainer,
            $childrenContainer
        );
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

        $hc = $this->newMockHeaderContainer();
        $instance = $this->newPartBuilder($hc);
        $this->assertTrue($instance->canHaveHeaders());

        $hc->expects($this->once())
            ->method('add')
            ->with('CONTENT-TYPE', 'kookoo-keekee');
        $hc->expects($this->once())
            ->method('get')
            ->with('Content-Type')
            ->willReturn($mockHeader);
        $instance->addHeader('CONTENT-TYPE', 'kookoo-keekee');
        $instance->setEndBoundaryFound('--Castle Black--');
        
        $child = $this->newPartBuilder(null, null, $instance);
        $this->assertFalse($child->canHaveHeaders());
    }

    public function testAddChildrenToContainer()
    {
        $instance = $this->newPartBuilder();
        $cc = $childrenContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParsedPartChildrenContainer')
                ->disableOriginalConstructor()
                ->getMock();

        $this->setContainers($instance, null, $cc);
        $f = $this->getMockForAbstractClass('ZBateson\MailMimeParser\Message\IMessagePart');
        $s = $this->getMockForAbstractClass('ZBateson\MailMimeParser\Message\IMessagePart');

        $cc->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive([ $f ], [ $s ]);

        $instance->addChildToContainer($f);
        $instance->addChildToContainer($s);
    }

    public function testAddAndGetRawHeaders()
    {
        $hc = $this->newMockHeaderContainer();
        $instance = $this->newPartBuilder($hc);
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
        $instance = $this->newPartBuilder($hc);
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

        $this->assertTrue($instance->isMimeMessagePart());
        $this->assertTrue($instance->isMimeMessagePart());
        $this->assertFalse($instance->isMimeMessagePart());
    }

    public function testGetContentType()
    {
        $hc = $this->newMockHeaderContainer();
        $instance = $this->newPartBuilder($hc);
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
        $instance = $this->newPartBuilder($hc);
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
        $instance = $this->newPartBuilder($hc);
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
        $instance = $this->newPartBuilder($hc);
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

        $child = $this->newPartBuilder(null, null, $instance);
        $this->assertEquals($instance, $child->getParent());
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
        
        $hcp = $this->newMockHeaderContainer();
        $hcp->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($mockParentHeader);
        $parent = $this->newPartBuilder($hcp);

        $instance = $this->newPartBuilder($hc, null, $parent);

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

        $hcp = $this->newMockHeaderContainer();
        $hcp->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($mockParentHeader);

        $parent = $this->newPartBuilder($hcp);
        $instance = $this->newPartBuilder($hc, null, $parent);

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
        $instance = $this->newPartBuilder();
        $instance->setStreamPartStartPos(42);
        $instance->setStreamPartEndPos(84);
        $this->assertEquals(42, $instance->getStreamPartStartOffset());
        $this->assertEquals(42, $instance->getStreamPartLength());
    }

    public function testSetStreamContentPosAndGetFilename()
    {
        $instance = $this->newPartBuilder();
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
        $super = $this->newPartBuilder();
        $parent = $this->newPartBuilder(null, null, $super);
        $instance = $this->newPartBuilder(null, null, $parent);

        $super->setStreamPartStartPos(0);
        $super->setStreamContentStartPos(3);
        $super->setStreamPartAndContentEndPos(3);

        $parent->setStreamPartStartPos(11);
        $parent->setStreamContentStartPos(13);
        $parent->setStreamPartAndContentEndPos(20);

        $instance->setStreamPartStartPos(22);
        $instance->setStreamContentStartPos(42);
        $instance->setStreamPartAndContentEndPos(84);

        $this->assertEquals(42, $instance->getStreamContentStartOffset());
        $this->assertEquals(84 - 42, $instance->getStreamContentLength());
        $this->assertEquals(22, $instance->getStreamPartStartOffset());
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
        $instance = $this->newPartBuilder();
        $instance->setProperty('island', 'Westeros');
        $instance->setProperty('capital', 'King\'s Landing');
        $this->assertSame('Westeros', $instance->getProperty('island'));
        $this->assertSame('King\'s Landing', $instance->getProperty('capital'));
        $this->assertNull($instance->getProperty('Joffrey\'s kindness'));
    }

    public function testCreateMessagePart()
    {
        $stream = Psr7\stream_for('thingsnstuff');
        $instance = $this->newPartBuilder();

        $this->parsedMessagePartFactory->expects($this->once())
            ->method('newInstance')
            ->with($instance, null)
            ->willReturn(true);
        $this->assertTrue($instance->createMessagePart($stream));
    }
}
