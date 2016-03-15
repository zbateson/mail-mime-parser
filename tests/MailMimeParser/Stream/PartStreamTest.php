<?php
namespace ZBateson\MailMimeParser\Stream;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\SimpleDi;

/**
 * Description of PartStreamTest
 *
 * @group PartStream
 * @covers ZBateson\MailMimeParser\Stream\PartStream
 * @author Zaahid Bateson
 */
class PartStreamTest extends PHPUnit_Framework_TestCase
{
    private $di;
    private $registry;
    
    protected function setUp()
    {
        $this->di = SimpleDi::singleton();
        $this->registry = $this->di->getPartStreamRegistry();
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
    
    public function testReadLimits()
    {
        $mem = fopen('php://memory', 'rw');
        fwrite($mem, 'This is a test');
        $this->registry->register('testReadLimits', $mem);
        
        $res = fopen('mmp-mime-message://testReadLimits?start=1&end=4', 'r');
        $this->assertNotNull($res);
        $str = stream_get_contents($res);
        $this->assertEquals('his', $str);
        
        fclose($res);
    }
    
    public function testReadLimitsToEnd()
    {
        $mem = fopen('php://memory', 'rw');
        fwrite($mem, 'test');
        $this->registry->register('testReadLimitsToEnd', $mem);
        
        $res = fopen('mmp-mime-message://testReadLimitsToEnd?start=0&end=4', 'r');
        $this->assertNotNull($res);
        $str = stream_get_contents($res);
        $this->assertEquals('test', $str);
        
        fclose($res);
    }
}
