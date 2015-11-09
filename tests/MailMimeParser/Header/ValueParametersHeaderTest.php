<?php
use ZBateson\MailMimeParser\SimpleDi as SimpleDi;

/**
 * Description of ValueParametersHeaderTest
 *
 * @group Headers
 * @group ValueParametersHeader
 * @author Zaahid Bateson
 */
class ValueParametersHeaderTest extends \PHPUnit_Framework_TestCase
{
    protected $headerFactory;
    
    public function setup()
    {
        $di = SimpleDi::singleton();
        $this->headerFactory = $di->getHeaderFactory();
    }
    
    public function testInstance()
    {
        $aValid = ['Content-Type', 'CONTENT-Disposition'];
        $aNot = ['MESSAGE-ID', 'bcc', 'Subject', 'X-Header-Test'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\ValueParametersHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\ValueParametersHeader', get_class($header));
        }
    }
    
    public function testParsingContentType()
    {
        $header = $this->headerFactory->newInstance('Content-Type', 'text/html; CHARSET="utf-8"');
        $this->assertNotNull($header);
        $this->assertEquals('text/html', $header->value);
        $this->assertEquals('utf-8', $header->params['charset']);
    }
    
    public function testParsingMultipleParts()
    {
        $header = $this->headerFactory->newInstance('Content-Type', 'TEXT/html; CHARSET=utf-8; Boundary="blooh";answer-to-everything==?US-ASCII?Q?42?=');
        $this->assertNotNull($header);
        $this->assertEquals('text/html', $header->value);
        $this->assertEquals('utf-8', $header->params['charset']);
        $this->assertEquals('blooh', $header->params['boundary']);
        $this->assertEquals('42', $header->params['answer-to-everything']);
    }
}
