<?php
use ZBateson\MailMimeParser\SimpleDi as SimpleDi;

/**
 * Description of HeaderTest
 *
 * @group Headers
 * @group Header
 * @author Zaahid Bateson
 */
class HeaderTest extends \PHPUnit_Framework_TestCase
{
    protected $headerFactory;
    
    public function setup()
    {
        $di = SimpleDi::singleton();
        $this->headerFactory = $di->getHeaderFactory();
    }
    
    public function testInstance()
    {
        $aValid = ['SUBject', 'X-Bah-Humbug', 'comment'];
        $aNot = ['MESSAGE-ID', 'bcc', 'ExPirY-daTE'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\Header', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\Header', get_class($header));
        }
    }
    
    public function testSimpleParsing()
    {
        $header = $this->headerFactory->newInstance('Subject', 'Il Principe "Niccolo Machiavelli"');
        $this->assertNotNull($header);
        $this->assertEquals('Il Principe "Niccolo Machiavelli"', $header->value);
        $this->assertEquals('Il Principe "Niccolo Machiavelli"', $header->part->value);
    }
    
    public function testMimeDecoder()
    {
        $header = $this->headerFactory->newInstance('Subject', '=?US-ASCII?Q?Jon?=
            =?US-ASCII?Q?at?=
            =?US-ASCII?Q?han?=');
        $this->assertNotNull($header);
        $this->assertEquals('Jonathan', $header->value);
    }
}
