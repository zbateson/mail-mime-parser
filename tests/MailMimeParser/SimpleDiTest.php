<?php
namespace ZBateson\MailMimeParser;

use PHPUnit_Framework_TestCase;

/**
 * Description of SimpleDiTest
 *
 * @group SimpleDi
 * @group Base
 * @covers ZBateson\MailMimeParser\SimpleDi
 * @author Zaahid Bateson
 */
class SimpleDiTest extends PHPUnit_Framework_TestCase
{
    public function testSingleton()
    {
        $di = SimpleDi::singleton();
        $this->assertNotNull($di);
        $this->assertInstanceOf('ZBateson\MailMimeParser\SimpleDi', $di);
        $this->assertSame($di, SimpleDi::singleton());
    }
    
    public function testNewMessageParser()
    {
        $di = SimpleDi::singleton();
        $mp = $di->newMessageParser();
        $this->assertNotNull($mp);
    }
    
    public function testNewCharsetConverter()
    {
        $di = SimpleDi::singleton();
        $m = $di->newCharsetConverter('ISO-8859-1', 'UTF-8');
        $this->assertNotNull($m);
    }
    
    public function testGetHeaderFactory()
    {
        $di = SimpleDi::singleton();
        $singleton = $di->getHeaderFactory();
        $this->assertNotNull($singleton);
        $this->assertSame($singleton, $di->getHeaderFactory());
    }
    
    public function testGetHeaderPartFactory()
    {
        $di = SimpleDi::singleton();
        $singleton = $di->getHeaderPartFactory();
        $this->assertNotNull($singleton);
        $this->assertSame($singleton, $di->getHeaderPartFactory());
    }
    
    public function testGetMimeLiteralPartFactory()
    {
        $di = SimpleDi::singleton();
        $singleton = $di->getMimeLiteralPartFactory();
        $this->assertNotNull($singleton);
        $this->assertSame($singleton, $di->getMimeLiteralPartFactory());
    }
    
    public function testGetConsumerService()
    {
        $di = SimpleDi::singleton();
        $singleton = $di->getConsumerService();
        $this->assertNotNull($singleton);
        $this->assertSame($singleton, $di->getConsumerService());
    }
}
