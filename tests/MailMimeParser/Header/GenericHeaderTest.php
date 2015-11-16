<?php

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\PartFactory;
use ZBateson\MailMimeParser\Header\GenericHeader;

/**
 * Description of StructuredHeaderTest
 *
 * @group Headers
 * @group GenericHeader
 * @author Zaahid Bateson
 */
class GenericHeaderTest extends PHPUnit_Framework_TestCase
{
    protected $consumerService;
    
    public function setup()
    {
        $pf = new PartFactory();
        $this->consumerService = new ConsumerService($pf);
    }
    
    /*public function testInstance()
    {
        $aValid = ['Content-Id', 'content-ID', 'IN-REPLY-TO'];
        $aNot = ['Subject', 'BCC', 'ExPirY-daTE'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\StructuredHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\StructuredHeader', get_class($header));
        }
    }*/
    
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
    
    public function testQuotesMimeAndComments()
    {
        $header = new GenericHeader(
            $this->consumerService,
            'Actor',
            '"Dwayne \"The Rock\"" =?US-ASCII?Q?Jackson?= (main actor)'
        );
        $this->assertEquals('Dwayne "The Rock" Jackson ', $header->getValue());
    }
}
