<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit_Framework_TestCase;

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
        $charsetConverter = $this->getMock('ZBateson\StreamDecorators\Util\CharsetConverter', ['__toString']);
        $pf = $this->getMock('ZBateson\MailMimeParser\Header\Part\HeaderPartFactory', ['__toString'], [$charsetConverter]);
        $mlpf = $this->getMock('ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory', ['__toString'], [$charsetConverter]);
        $this->consumerService = $this->getMock('ZBateson\MailMimeParser\Header\Consumer\ConsumerService', ['__toString'], [$pf, $mlpf]);
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
