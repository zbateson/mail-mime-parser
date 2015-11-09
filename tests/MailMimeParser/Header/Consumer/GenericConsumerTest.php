<?php

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\PartFactory;

/**
 * Description of GenericConsumerTest
 *
 * @group Consumers
 * @group GenericConsumer
 * @author Zaahid Bateson
 */
class GenericConsumerTest extends PHPUnit_Framework_TestCase
{
    private $genericConsumer;
    
    public function setUp()
    {
        $pf = new PartFactory();
        $cs = new ConsumerService($pf);
        $this->genericConsumer = $cs->getGenericConsumer();
    }
    
    public function tearDown()
    {
        unset($this->genericConsumer);
    }
    
    public function testConsumeTokens()
    {
        $value = "Je\ \t suis\nici";
        $parts = ['Je', ' ', "\t ", 'suis', "\n", 'ici'];

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Je  suis ici', $ret[0]);
    }
}
