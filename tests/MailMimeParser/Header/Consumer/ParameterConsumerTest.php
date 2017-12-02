<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit_Framework_TestCase;

/**
 * Description of ParameterConsumerTest
 *
 * @group Consumers
 * @group ParameterConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\ParameterConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class ParameterConsumerTest extends PHPUnit_Framework_TestCase
{
    private $parameterConsumer;
    
    protected function setUp()
    {
        $charsetConverter = $this->getMock('ZBateson\MailMimeParser\Util\CharsetConverter', ['__toString']);
        $pf = $this->getMock('ZBateson\MailMimeParser\Header\Part\HeaderPartFactory', ['__toString'], [$charsetConverter]);
        $mlpf = $this->getMock('ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory', ['__toString'], [$charsetConverter]);
        $cs = $this->getMock('ZBateson\MailMimeParser\Header\Consumer\ConsumerService', ['__toString'], [$pf, $mlpf]);
        $this->parameterConsumer = new ParameterConsumer($cs, $pf);
    }
    
    public function testConsumeTokens()
    {
        $ret = $this->parameterConsumer->__invoke('text/html; charset=utf8');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\LiteralPart', $ret[0]);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ParameterPart', $ret[1]);
        $this->assertEquals('text/html', $ret[0]->getValue());
        $this->assertEquals('charset', $ret[1]->getName());
        $this->assertEquals('utf8', $ret[1]->getValue());
    }
    
    public function testEscapedSeparators()
    {
        $ret = $this->parameterConsumer->__invoke('test\;with\;special\=chars; and\=more=blah');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('test;with;special=chars', $ret[0]->getValue());
        $this->assertEquals('and=more', $ret[1]->getName());
        $this->assertEquals('blah', $ret[1]->getValue());
    }
    
    public function testWithSubConsumers()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; weiner="all-beef";toppings=sriracha (boo-yah!)');
        $this->assertNotEmpty($ret);
        $this->assertCount(3, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('weiner', $ret[1]->getName());
        $this->assertEquals('all-beef', $ret[1]->getValue());
        $this->assertEquals('toppings', $ret[2]->getName());
        $this->assertEquals('sriracha', $ret[2]->getValue());
    }
}
