<?php
namespace ZBateson\MailMimeParser\Message;

use LegacyPHPUnit\TestCase;

/**
 * Description of PartChildrenContainerTest
 *
 * @group Message
 * @group PartChildrenContainer
 * @covers ZBateson\MailMimeParser\Message\PartChildrenContainer
 * @author Zaahid Bateson
 */
class PartChildrenContainerTest extends TestCase
{
    protected $instance;

    protected function legacySetUp()
    {
        $this->instance = new PartChildrenContainer();
    }

    private function getIMessagePart()
    {
        return $this->getMockForAbstractClass(
            'ZBateson\MailMimeParser\Message\IMessagePart'
        );
    }

    private function getIMultiPart()
    {
        return $this->getMockForAbstractClass(
            'ZBateson\MailMimeParser\Message\IMultiPart'
        );
    }

    public function testHasAndGetChildren()
    {
        $this->assertFalse($this->instance->hasChildren());
        $part = $this->getIMultiPart();
        $this->instance->add($part);
        $this->assertFalse($this->instance->hasChildren());
        $this->assertNull($this->instance->getChildren());
        
        $part->method('getChildIterator')->willReturn($this->instance);
        $this->assertTrue($this->instance->hasChildren());
        $this->assertEquals($this->instance, $this->instance->getChildren());

        $part2 = $this->getIMessagePart();
        $t = new PartChildrenContainer([ $part2, $part ]);
        $this->assertFalse($t->hasChildren());
        $this->assertNull($t->getChildren());

        $t->next();
        $this->assertTrue($t->hasChildren());
        $this->assertEquals($this->instance, $t->getChildren());

        $t->next();
        $this->assertFalse($t->hasChildren());
        $this->assertNull($t->getChildren());
    }

    public function testIterator()
    {
        $this->assertFalse($this->instance->valid());
        $this->assertNull($this->instance->current());
        $this->assertEquals(0, $this->instance->key());
        $this->instance->next();
        $this->assertEquals(1, $this->instance->key());
        $this->assertFalse($this->instance->valid());
        $this->assertNull($this->instance->current());

        $arr = [ $this->getIMultiPart(), $this->getIMultiPart(), $this->getIMultiPart(), $this->getIMultiPart() ];
        $t = new PartChildrenContainer($arr);

        foreach ($arr as $k => $p) {
            $this->assertTrue($t->valid());
            $this->assertSame($p, $t->current());
            $this->assertEquals($k, $t->key());
            $t->next();
        }
        $this->assertFalse($t->valid());
        $this->assertNull($t->current());
        $t->rewind();

        $this->assertTrue($t->valid());
        $this->assertSame($arr[0], $t->current());
        $this->assertEquals(0, $t->key());
    }

    public function testArrayAccess()
    {
        $this->assertFalse($this->instance->offsetExists(0));
        $this->assertFalse(isset($this->instance[0]));
        $this->assertNull($this->instance[0]);

        $arr = [ $this->getIMultiPart(), $this->getIMultiPart(), $this->getIMultiPart() ];
        $t = new PartChildrenContainer($arr);

        foreach ($arr as $k => $p) {
            $this->assertTrue($t->offsetExists($k));
            $this->assertTrue(isset($t[$k]));
            $this->assertSame($p, $t[$k]);
        }
        $this->assertFalse($t->offsetExists(count($arr)));
        $this->assertNull($t[count($arr)]);

        $n = $this->getIMultiPart();
        $t[] = $n;
        $this->assertTrue(isset($t[count($arr)]));
        $this->assertSame($n, $t[count($arr)]);

        $n2 = $this->getIMultiPart();
        $t[1] = $n2;
        $this->assertSame($arr[0], $t[0]);
        $this->assertSame($n2, $t[1]);
        $this->assertSame($arr[2], $t[2]);
    }
}
