<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of GenericHeaderTest
 *
 * @group Headers
 * @group GenericHeader
 * @covers ZBateson\MailMimeParser\Header\GenericHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class GenericHeaderTest extends PHPUnit_Framework_TestCase
{
    protected $consumerService;
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $this->consumerService = new ConsumerService($pf, $mlpf);
    }
    
    public function testParsing()
    {
        $header = new GenericHeader($this->consumerService, 'Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
        $this->assertEquals('Hunter S. Thompson', $header->getRawValue());
        $this->assertCount(1, $header->getParts());
        $this->assertEquals('Hunted-By', $header->getName());
    }
    
    public function testMultilineMimeParts()
    {
        $header = new GenericHeader($this->consumerService, 'Hunted-By', '=?US-ASCII?Q?Hunt?=
             =?US-ASCII?Q?er_S._?=
             =?US-ASCII?Q?Thompson?=');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
    }
    
    /**
     * 
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer::isStartToken
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer::isEndToken
     */
    public function testQuotesMimeAndComments()
    {
        $header = new GenericHeader(
            $this->consumerService,
            'Actor',
            '"Dwayne \"The Rock\"" =?US-ASCII?Q?Jackson?= (main actor)'
        );
        $this->assertEquals('Dwayne "The Rock" Jackson', $header->getValue());
    }
    
    public function testCommentBetweenParts()
    {
        $header = new GenericHeader(
            $this->consumerService,
            'Actor',
            'Dwayne (The Rock) Jackson'
        );
        $this->assertEquals('Dwayne Jackson', $header->getValue());
    }
    
    public function testGenericHeaderToString()
    {
        $header = new GenericHeader($this->consumerService, 'Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunted-By: Hunter S. Thompson', $header);
    }
}
