<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;

/**
 * Description of QuotedStringConsumerTest
 *
 * @group Consumers
 * @group QuotedStringConsumer
 * @author Zaahid Bateson
 */
class QuotedStringConsumerTest extends PHPUnit_Framework_TestCase
{
    private $quotedStringConsumer;
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $cs = new ConsumerService($pf);
        $this->quotedStringConsumer = QuotedStringConsumer::getInstance($cs, $pf);
    }
    
    public function testConsumeTokens()
    {
        $value = 'Will end at " quote';
        
        $ret = $this->quotedStringConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Will end at ', $ret[0]);
    }
}
