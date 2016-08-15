<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit_Framework_TestCase;

/**
 * Description of AbstractConsumerTest
 *
 * @group Consumers
 * @group AbstractConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class AbstractConsumerTest extends PHPUnit_Framework_TestCase
{
    private $abstractConsumerStub;
    
    protected function setUp()
    {
        $stub = $this->getMockBuilder('\ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer')
            ->setMethods(['combineParts', 'isEndToken', 'getPartForToken', 'getTokenSeparators', 'getSubConsumers'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $stub->method('isEndToken')
            ->willReturn(false);
        $stub->method('getTokenSeparators')
            ->willReturn(['\s+']);
        $stub->method('getSubConsumers')
            ->willReturn([]);
        
        $this->abstractConsumerStub = $stub;
    }
    
    public function testSingleToken()
    {
        $value = 'teapot';
        $stub = $this->abstractConsumerStub;
        
        $stub->expects($this->once())
            ->method('getPartForToken')
            ->with($value);
        $stub->method('combineParts')
            ->willReturn([$value]);
        
        $ret = $stub($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
    }
    
    public function testMultipleTokens()
    {
        $value = "Je\ \t suis\nici";
        $parts = ['Je', ' ', "\t ", 'suis', "\n", 'ici'];
        
        $stub = $this->abstractConsumerStub;

        $stub->expects($this->exactly(6))
            ->method('getPartForToken')
            ->withConsecutive([$parts[0]], [$parts[1]], [$parts[2]], [$parts[3]], [$parts[4]], [$parts[5]])
            ->will($this->onConsecutiveCalls($parts[0], $parts[1], $parts[2], $parts[3], $parts[4], $parts[5]));
        $stub->method('combineParts')
            ->willReturn($parts);
        
        $ret = $stub($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(6, $ret);
    }
    
    public function testInvokeWithEmptyValue()
    {
        $stub = $this->abstractConsumerStub;
        $ret = $stub('');
        $this->assertEmpty($ret);
        $this->assertEquals([], $ret);
    }
}
