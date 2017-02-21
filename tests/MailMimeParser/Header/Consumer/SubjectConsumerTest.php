<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of SubjectConsumerTest
 *
 * @group Consumers
 * @group SubjectConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\SubjectConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class SubjectConsumerTest extends PHPUnit_Framework_TestCase
{
    private $subjectConsumer;
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $cs = new ConsumerService($pf, $mlpf);
        $this->subjectConsumer = SubjectConsumer::getInstance($cs, $mlpf);
    }
    
    public function testConsumeTokens()
    {
        $value = "Je\ \t suis\nici";
        
        $ret = $this->subjectConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Je\ suis ici', $ret[0]);
    }
    
    public function testFilterSpacesBetweenMimeParts()
    {
        $value = "=?US-ASCII?Q?Je?=    =?US-ASCII?Q?suis?=\n=?US-ASCII?Q?ici?=";
        
        $ret = $this->subjectConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Jesuisici', $ret[0]);
    }
}
