<?php
namespace ZBateson\MailMimeParser\Stream;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\SimpleDi;

/**
 * Description of PartStreamRegistryTest
 *
 * @gropu Stream
 * @group PartStreamRegistry
 * @covers ZBateson\MailMimeParser\Stream\PartStreamRegistry
 * @author Zaahid Bateson
 */
class PartStreamRegistryTest extends PHPUnit_Framework_TestCase
{
    private $registry;
    
    protected function setUp()
    {
        $di = SimpleDi::singleton();
        $this->registry = $di->getPartStreamRegistry();
    }
    
    public function testRegisteringAndUnregistering()
    {
        $mem = fopen('php://memory', 'rw');
        fwrite($mem, 'This is a test');
        $mem2 = fopen('php://memory', 'rw');
        fwrite($mem2, 'This is a test');
        $mem3 = fopen('php://memory', 'rw');
        fwrite($mem3, 'This is a test');
        
        $this->registry->register(1, $mem);
        $this->registry->register(2, $mem2);
        $this->registry->register(3, $mem3);
        
        $ps = @fopen('mmp-mime-message://1?start=1&end=4', 'r');
        $ps2 = @fopen('mmp-mime-message://2?start=1&end=4', 'r');
        $ps3 = @fopen('mmp-mime-message://3?start=1&end=4', 'r');
        
        $this->assertNotNull($ps);
        $this->assertNotNull($ps2);
        $this->assertNotNull($ps3);
        
        $this->assertSame($mem, $this->registry->get(1));
        $this->assertSame($mem2, $this->registry->get(2));
        $this->assertSame($mem3, $this->registry->get(3));
        
        $this->registry->increaseHandleRefCount(1);
        $this->assertSame($mem, $this->registry->get(1));
        $this->registry->decreaseHandleRefCount(1);
        $this->assertSame($mem, $this->registry->get(1));
        $this->registry->decreaseHandleRefCount(1);
        $this->assertNull($this->registry->get(1));
        
        fclose($ps);
        fclose($ps2);
        fclose($ps3);
        
        $ps2 = @fopen('mmp-mime-message://2?start=1&end=4', 'r');
        $this->assertFalse($ps2);
        
        $ps = @fopen('mmp-mime-message://1?start=1&end=4', 'r');
        $this->assertFalse($ps);
        
        $ps3 = @fopen('mmp-mime-message://3?start=1&end=4', 'r');
        $this->assertFalse($ps3);
    }
}
