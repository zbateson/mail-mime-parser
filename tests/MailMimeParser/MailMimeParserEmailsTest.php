<?php

use ZBateson\MailMimeParser\MailMimeParser;

/**
 * Description of MailMimeParserEmails
 *
 * @group MailMimeParserEmails
 * @author Zaahid Bateson
 */
class MailMimeParserEmailsTest extends PHPUnit_Framework_TestCase
{
    private $parser;
    private $messageDir;
    
    public function setup()
    {
        $this->parser = new MailMimeParser();
        $this->messageDir = dirname(__DIR__) . '/' . TEST_DATA_DIR . '/emails';
    }
    
    public function assertStringEqualsIgnoreWhiteSpace($test, $str, $message = null)
    {
        $this->assertEquals(
            preg_replace('/\s+/', ' ', $test),
            preg_replace('/\s+/', ' ', $str),
            $message
        );
    }
    
    public function testPlainText()
    {
        $messages = [
            'm0001' => $this->parser->parse(fopen($this->messageDir . '/m0001.txt', 'r')),
            'm0002' => $this->parser->parse(fopen($this->messageDir . '/m1001.txt', 'r')),
            'm0003' => $this->parser->parse(fopen($this->messageDir . '/m2001.txt', 'r')),
        ];
        $text = file_get_contents($this->messageDir . '/files/HasenundFrФsche.txt');
        
        foreach ($messages as $key => $message) {
            
            $p = $message->getTextPart();
            $f = $message->getTextStream();
            $this->assertNotNull($p, $key);
            $this->assertNotNull($f, $key);
            
            $to = $message->getHeader('to');
            $this->assertEquals('Jürgen Schmürgen', $to->getPersonName(), $key);
            $this->assertEquals('schmuergen@example.com', $to->getValue(), $key);
            
            $from = $message->getHeader('From');
            $this->assertNotNull($from, $key);
            $this->assertEquals('Doug Sauder', $from->getPersonName(), $key);
            $this->assertEquals('doug@example.com', $from->getValue(), $key);
            $this->assertEquals('Die Hasen und die Frösche', $message->getHeaderValue('subject'), $key);
            
            $str = stream_get_contents($f);
            $this->assertStringEqualsIgnoreWhiteSpace($text, $str, $key);
        }
    }
    
    /*public function testEmailWithAttachment()
    {
        $message = $this->parser->parse(fopen(dirname(__DIR__) . '/' . TEST_DATA_DIR . '/emails/3.txt', 'r'));
        
        $f = $message->getTextStream();
        $this->assertNotNull($f);
        $str = stream_get_contents($f);
        $this->assertContains('Sent from my iPad', $str);
    }
    
    public function testEmailFromAndroid()
    {
        $message = $this->parser->parse(fopen(dirname(__DIR__) . '/' . TEST_DATA_DIR . '/emails/android.txt', 'r'));
        
        $f = $message->getTextStream();
        $this->assertNotNull($f);
        $str = stream_get_contents($f);
        $this->assertContains('Sent from my Verizon Wireless 4GLTE smartphone', $str);
        
        $f = $message->getHtmlStream();
        $this->assertNotNull($f);
        $str = stream_get_contents($f);
        $this->assertContains('Sent from my Verizon Wireless 4GLTE smartphone', $str);
    }*/
}
