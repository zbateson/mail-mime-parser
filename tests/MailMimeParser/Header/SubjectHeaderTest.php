<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit_Framework_TestCase;

/**
 * Description of SubjectHeader
 *
 * @group Headers
 * @group SubjectHeader
 * @covers ZBateson\MailMimeParser\Header\SubjectHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class SubjectHeaderTest extends PHPUnit_Framework_TestCase
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
        $header = new SubjectHeader($this->consumerService, 'Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
        $this->assertEquals('Hunter S. Thompson', $header->getRawValue());
        $this->assertCount(1, $header->getParts());
        $this->assertEquals('Hunted-By', $header->getName());
    }
    
    public function testMultilineMimeParts()
    {
        $header = new SubjectHeader($this->consumerService, 'Hunted-By', '=?US-ASCII?Q?Hunt?=
             =?US-ASCII?Q?er_S._?=
             =?US-ASCII?Q?Thompson?=');
        $this->assertEquals('Hunter S. Thompson', $header->getValue());
    }
    
    public function testMultilineMimePartWithParentheses()
    {
        $header = new SubjectHeader($this->consumerService, 'Hunted-By', ' =?koi8-r?B?9MXIzsnexdPLycUg0sHCz9TZIChFUlAg58HMwcvUycvBIMkg79TexdTZIPTk?=
            =?koi8-r?Q?)?=');
        $this->assertEquals('Технические работы (ERP Галактика и Отчеты ТД)', $header->getValue());
    }
    
    /**
     * 
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer::isStartToken
     * @covers ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer::isEndToken
     */
    public function testQuotesMimeAndComments()
    {
        $header = new SubjectHeader(
            $this->consumerService,
            'Actor',
            '"Dwayne \"The Rock\"" =?US-ASCII?Q?Jackson?= (main actor)'
        );
        $this->assertEquals('"Dwayne \"The Rock\"" Jackson (main actor)', $header->getValue());
    }
    
    public function testCommentBetweenParts()
    {
        $header = new SubjectHeader(
            $this->consumerService,
            'Actor',
            'Dwayne (The Rock) Jackson'
        );
        $this->assertEquals('Dwayne (The Rock) Jackson', $header->getValue());
    }
    
    public function testSubjectHeaderToString()
    {
        $header = new SubjectHeader($this->consumerService, 'Hunted-By', 'Hunter S. Thompson');
        $this->assertEquals('Hunted-By: Hunter S. Thompson', $header);
    }
}
