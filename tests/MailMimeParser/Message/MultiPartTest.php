<?php
namespace ZBateson\MailMimeParser\Message;

use LegacyPHPUnit\TestCase;
use ZBateson\MailMimeParser\Message\PartChildrenContainer;

/**
 * Description of MultiPartTest
 *
 * @group MultiPart
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\MimePart
 * @covers ZBateson\MailMimeParser\Message\MultiPart
 * @covers ZBateson\MailMimeParser\Message\MessagePart
 * @author Zaahid Bateson
 */
class MultiPartTest extends TestCase
{
    private $mockPartStreamContainer;
    private $mockHeaderContainer;
    private $partChildrenContainer;

    private $allParts;
    private $children;
    private $secondChildNested;

    protected function legacySetUp()
    {
        $this->mockPartStreamContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartStreamContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockHeaderContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partChildrenContainer = new PartChildrenContainer();
    }

    private function getParentMimePart()
    {
        $parentContainer = new PartChildrenContainer();
        $parent = $this->getMimePart($parentContainer);

        $nestedContainer = new PartChildrenContainer();

        $nested = $this->getMimePart($nestedContainer, null, null, $parent);
        $this->children = [
            $this->getMimePart(null, null, null, $parent),
            $nested,
            $this->getMimePart(null, null, null, $parent)
        ];
        $this->secondChildNested = [
            $this->getMimePart(null, null, null, $nested),
            $this->getMimePart(null, null, null, $nested)
        ];

        $parentContainer->add($this->children[0]);
        $parentContainer->add($this->children[1]);
        $parentContainer->add($this->children[2]);

        $nestedContainer->add($this->secondChildNested[0]);
        $nestedContainer->add($this->secondChildNested[1]);

        $this->allParts = [
            $parent,
            $this->children[0],
            $this->children[1],
            $this->secondChildNested[0],
            $this->secondChildNested[1],
            $this->children[2]
        ];

        return $parent;
    }

    private function getMimePart($childrenContainer = null, $headerContainer = null, $streamContainer = null, $parent = null)
    {
        if ($childrenContainer === null) {
            $childrenContainer = $this->partChildrenContainer;
        }
        if ($headerContainer === null) {
            $headerContainer = $this->mockHeaderContainer;
        }
        if ($streamContainer === null) {
            $streamContainer = $this->mockPartStreamContainer;
        }
        return new MimePart($parent, $streamContainer, $headerContainer, $childrenContainer);
    }

    protected function getMockedParameterHeader($name, $value, $parameterValue = null)
    {
        $header = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'getRawValue', 'getName', 'getValueFor', 'hasParameter'])
            ->getMock();
        $header->method('getName')->willReturn($name);
        $header->method('getValue')->willReturn($value);
        $header->method('getRawValue')->willReturn($value);
        $header->method('getValueFor')->willReturn($parameterValue);
        $header->method('hasParameter')->willReturn(true);
        return $header;
    }

    public function testIsMultiPart()
    {
        $part = $this->getMimePart();

        $this->mockHeaderContainer->expects($this->atLeastOnce())
            ->method('get')
            ->with($this->equalTo('Content-Type'))
            ->willReturnOnConsecutiveCalls(
                $this->getMockedParameterHeader('Content-Type', 'bleek/blorp'),
                $this->getMockedParameterHeader('Content-Type', 'multipart/mixed'),
                $this->getMockedParameterHeader('Content-Type', 'multipart/related'),
                $this->getMockedParameterHeader('Content-Type', 'multipart/alternative'),
                $this->getMockedParameterHeader('Content-Type', 'multipart/signed'),
                $this->getMockedParameterHeader('Content-Type', 'multipart/anything'),
                $this->getMockedParameterHeader('Content-Type', 'something/else')
            );
        
        $this->assertFalse($part->isMultiPart());
        $this->assertTrue($part->isMultiPart());
        $this->assertTrue($part->isMultiPart());
        $this->assertTrue($part->isMultiPart());
        $this->assertTrue($part->isMultiPart());
        $this->assertTrue($part->isMultiPart());
        $this->assertFalse($part->isMultiPart());
    }

    public function testGetChildrenParts()
    {
        $part = $this->getParentMimePart();

        $this->assertEquals(3, $part->getChildCount());

        $this->assertSame($this->children[0], $part->getChild(0));
        $this->assertSame($this->children[1], $part->getChild(1));
        $this->assertSame($this->children[2], $part->getChild(2));
        $this->assertEquals($this->children, $part->getChildParts());

        $iter = $part->getChildIterator();
        $this->assertInstanceOf('\Iterator', $iter);
        foreach ($iter as $k => $p) {
            $this->assertSame($this->children[$k], $p);
        }
    }

    public function testGetChildrenPartsWithFilters()
    {
        $part = $this->getParentMimePart();
        $children = $this->children;
        $fnFilter = function ($part) use ($children) {
            return ($part === $children[1]);
        };
        $this->assertEquals(1, $part->getChildCount($fnFilter));
        $this->assertSame($children[1], $part->getChild(0, $fnFilter));
        $this->assertEquals([ $children[1] ], $part->getChildParts($fnFilter));
    }

    public function testGetAllParts()
    {
        $part = $this->getParentMimePart();

        $this->assertEquals(6, $part->getPartCount());
        foreach ($this->allParts as $key => $p) {
            $this->assertSame($p, $part->getPart($key));
        }

        $this->assertEquals($this->allParts, $part->getAllParts());
    }

    public function testGetAllPartsWithFilters()
    {
        $part = $this->getParentMimePart();
        $parts = $this->allParts;
        $fnFilter = function ($part) use ($parts) {
            return ($part === $parts[1] || $part === $parts[3]);
        };

        $this->assertEquals(2, $part->getPartCount($fnFilter));
        $this->assertSame($parts[1], $part->getPart(0, $fnFilter));
        $this->assertSame($parts[3], $part->getPart(1, $fnFilter));
        $this->assertEquals([ $parts[1], $parts[3] ], $part->getAllParts($fnFilter));
    }

    public function testGetAllPartsByMimeType()
    {
        $this->mockHeaderContainer->expects($this->atLeastOnce())
            ->method('get')
            ->with($this->equalTo('Content-Type'))
            ->willReturn($this->getMockedParameterHeader('Content-Type', 'Smiling'));
        $matching = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $matching->expects($this->atLeastOnce())
            ->method('get')
            ->with($this->equalTo('Content-Type'))
            ->willReturn($this->getMockedParameterHeader('Content-Type', 'Ecstatic'));

        $parentContainer = new PartChildrenContainer();
        $parent = $this->getMimePart($parentContainer, $matching);

        $children = [
            $this->getMimePart(null, null, null, $parent),
            $this->getMimePart(null, $matching, null, $parent),
            $this->getMimePart(null, null, null, $parent),
            $this->getMimePart(null, null, null, $parent)
        ];
        $parentContainer->add($children[0]);
        $parentContainer->add($children[1]);
        $parentContainer->add($children[2]);
        $parentContainer->add($children[3]);

        $this->assertEquals(2, $parent->getCountOfPartsByMimeType('Ecstatic'));
        $this->assertSame($parent, $parent->getPartByMimeType('Ecstatic'));
        $this->assertSame($children[1], $parent->getPartByMimeType('Ecstatic', 1));
        $this->assertEquals([ $parent, $children[1] ], $parent->getAllPartsByMimeType('Ecstatic'));
    }

    public function testGetPartByContentId()
    {
        $this->mockHeaderContainer->expects($this->atLeastOnce())
            ->method('get')
            ->with($this->equalTo('Content-ID'))
            ->willReturn(null);
        $matching = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();

        // IdHeader filters out surrounding whitespace and <> characters, but here I'm using ParameterHeader so
        // 'bar-of-foo' must not be surrounded by either to match below
        $matching->expects($this->atLeastOnce())
            ->method('get')
            ->with($this->equalTo('Content-ID'))
            ->willReturn($this->getMockedParameterHeader('Content-ID', 'bar-of-foo'));

        $parentContainer = new PartChildrenContainer();
        $parent = $this->getMimePart($parentContainer, null);

        $children = [
            $this->getMimePart(null, null, null, $parent),
            $this->getMimePart(null, $matching, null, $parent),
            $this->getMimePart(null, null, null, $parent),
            $this->getMimePart(null, null, null, $parent)
        ];
        $parentContainer->add($children[0]);
        $parentContainer->add($children[1]);
        $parentContainer->add($children[2]);
        $parentContainer->add($children[3]);

        $this->assertSame($children[1], $parent->getPartByContentId(' <bar-of-foo>   '));
    }

    public function testAddChild()
    {
        $part = $this->getParentMimePart();
        // for clarity
        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());

        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $part->attach($observer);

        $new = $this->getMimePart();
        $part->addChild($new);
        $this->allParts[] = $new;
        $this->children[] = $new;

        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());
        $this->assertSame($part, $new->getParent());
    }

    public function testRemovePart()
    {
        $part = $this->getParentMimePart();
        // for clarity
        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());

        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $part->attach($observer);

        $this->assertEquals(0, $part->removePart($this->secondChildNested[0]));
        array_splice($this->allParts, 3, 1);
        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());
    }

    public function testRemovePartAndAddChild()
    {
        $part = $this->getParentMimePart();
        // for clarity
        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());

        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->exactly(2))
            ->method('update');
        $part->attach($observer);

        $this->assertSame($this->children[1], $this->secondChildNested[0]->getParent());
        $part->removePart($this->secondChildNested[0]);
        $part->addChild($this->secondChildNested[0], 0);
        $this->assertSame($part, $this->secondChildNested[0]->getParent());
        
        array_splice($this->allParts, 3, 1);
        array_splice($this->allParts, 1, 0, [ $this->secondChildNested[0] ]);
        array_unshift($this->children, $this->secondChildNested[0]);

        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());
    }

    public function testRemoveAllPartsFromChild()
    {
        $part = $this->getParentMimePart();
        // for clarity
        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());

        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->exactly(2))
            ->method('update');
        $part->attach($observer);

        $this->assertEquals(2, $this->children[1]->getChildCount());
        $this->children[1]->removeAllParts();
        $this->assertEquals(0, $this->children[1]->getChildCount());
        array_splice($this->allParts, 3, 2);
        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());
    }

    public function testRemoveAllPartsWithFilter()
    {
        $part = $this->getParentMimePart();
        // for clarity
        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());

        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $part->attach($observer);

        $rm = $this->secondChildNested[0];
        $part->removeAllParts(function ($p) use ($rm) {
            if ($p === $rm) {
                return true;
            }
        });
        array_splice($this->allParts, 4, 1);
        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());
    }

    public function testRemoveAllParts()
    {
        $part = $this->getParentMimePart();
        // for clarity
        $this->assertEquals($this->allParts, $part->getAllParts());
        $this->assertEquals($this->children, $part->getChildParts());

        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->exactly(count($this->allParts) - 1))
            ->method('update');
        $part->attach($observer);

        $part->removeAllParts();
        $this->assertEquals(1, $part->getPartCount());
        $this->assertEquals([ $part ], $part->getAllParts());
        $this->assertEquals(0, $part->getChildCount());
        $this->assertEquals([], $part->getChildParts());
    }
}
