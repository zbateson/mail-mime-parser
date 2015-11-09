<?php
use ZBateson\MailMimeParser\SimpleDi as SimpleDi;

/**
 * Description of StructuredHeaderTest
 *
 * @group Headers
 * @group StructuredHeader
 * @author Zaahid Bateson
 */
class StructuredHeaderTest extends \PHPUnit_Framework_TestCase
{
    protected $headerFactory;
    
    public function setup()
    {
        $di = SimpleDi::singleton();
        $this->headerFactory = $di->getHeaderFactory();
    }
    
    public function testInstance()
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
    }
    
    public function testSimpleParsing()
    {
        $header = $this->headerFactory->newInstance('Content-Id', '123');
        $this->assertNotNull($header);
        $this->assertEquals('123', $header->value);
        $this->assertCount(1, $header->parts);
        $this->assertEquals('123', $header->parts[0]->value);
    }
    
    public function testMimeDecoder()
    {
        $header = $this->headerFactory->newInstance('CONTENT-ID', '=?US-ASCII?Q?Jon?=
            =?US-ASCII?Q?at?=
            =?US-ASCII?Q?han?=');
        $this->assertNotNull($header);
        $this->assertEquals('Jonathan', $header->value);
    }
    
    public function testQuotes()
    {
        $header = $this->headerFactory->newInstance(
            'in-reply-to',
            'This   "is   a \"test\" =?US-ASCII?Q?Jon?= (sweetness)"'
        );
        $this->assertNotNull($header);
        $this->assertEquals('This is   a "test" =?US-ASCII?Q?Jon?= (sweetness)', $header->value);
    }
    
    public function testComments()
    {
        $header = $this->headerFactory->newInstance(
            'in-reply-to',
            'This ("is   a \"test\" =?US-ASCII?Q?Jon?= (sweetness)")'
        );
        $this->assertNotNull($header);
        $this->assertEquals('This', $header->value);
        
        $this->assertCount(2, $header->parts);
        $this->assertEquals('This ', $header->parts[0]->value);
        $this->assertEmpty($header->parts[1]->value);
        $this->assertEquals('is   a "test" =?US-ASCII?Q?Jon?= (sweetness)', $header->parts[1]->comment);
    }
    
    public function testMixedParts()
    {
        $header = $this->headerFactory->newInstance(
            'CONTENT-id',
            '=?US-ASCII?Q?Jon?=
                =?US-ASCII?Q?at?=
                =?US-ASCII?Q?han?=This is not =?US-ASCII?Q?Hagar?=\'s doing
                "To kill \"many\" (mockingbirds)"
                (For =?US-ASCII?Q?Sagan?= (isn\'t everything? (yes)))'
        );
        $this->assertNotNull($header);
        $this->assertEquals('JonathanThis is not Hagar\'s doing ' .
            'To kill "many" (mockingbirds)', $header->value);
        $this->assertCount(4, $header->parts);
        $this->assertEquals('For Sagan (isn\'t everything? (yes))', $header->parts[3]->comment);
    }
}
