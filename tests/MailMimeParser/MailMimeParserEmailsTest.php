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
    
    private $emailList = [
        'm0001' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt'
        ],
        'm0002' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt'
        ],
        'm0003' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt'
        ],
        'm0004' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrФsche.txt'
        ],
        'm0005' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrФsche.txt'
        ],
        'm0006' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'jblow@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrФsche.txt'
        ],
        'm0007' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrФsche.txt'
        ],
        'm0008' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrФsche.txt'
        ],
        'm0009' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt'
        ],
        'm0010' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrФsche.txt'
        ],
        'm0011' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Test message from Microsoft Outlook 00',
            'text' => 'hareandtortoise.txt',
            'attachments' => 3
        ],
        'm0012' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'jblow@example.com'
            ],
            'Subject' => 'Test message from Microsoft Outlook 00',
            'attachments' => 1
        ],
        'm0013' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'jblow@example.com'
            ],
            'Subject' => 'Test message from Microsoft Outlook 00',
            'attachments' => 2
        ],
        'm0014' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'jblow@example.com'
            ],
            'Subject' => 'Test message from Microsoft Outlook 00',
            'text' => 'hareandtortoise.txt',
            'html' => 'hareandtortoise.txt',
        ],
        'm0015' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'jblow@example.com'
            ],
            'Subject' => 'Test message from Microsoft Outlook 00',
            'text' => 'hareandtortoise.txt',
            'html' => 'hareandtortoise.txt',
            'attachments' => 2,
        ],
        'm0016' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'jblow@example.com'
            ],
            'Subject' => 'Test message from Microsoft Outlook 00',
            'text' => 'hareandtortoise.txt',
        ],
        'm0016' => [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'jblow@example.com'
            ],
            'Subject' => 'Test message from Microsoft Outlook 00',
            'text' => 'hareandtortoise.txt',
            'html' => 'hareandtortoise.txt',
            'attachments' => 3,
        ],
    ];
    
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
    
    public function testEmailFiles()
    {
        foreach ($this->emailList as $key => $props) {
            
            $handle = fopen($this->messageDir . '/' . $key . '.txt', 'r');
            $message = $this->parser->parse($handle);
            fclose($handle);
            
            $failMessage = 'Failed while parsing ' . $key;
            
            if (isset($props['text'])) {
                $f = $message->getTextStream();
                $this->assertNotNull($f, $failMessage);
                $str = stream_get_contents($f);
                $text = mb_convert_encoding(file_get_contents($this->messageDir . '/files/' . $props['text']), 'UTF-8', 'ISO-8859-1');
                $this->assertStringEqualsIgnoreWhiteSpace($text, $str, $failMessage);
            }
            
            if (isset($props['html'])) {
                $f = $message->getHtmlStream();
                $this->assertNotNull($f, $failMessage);
                $str = htmlspecialchars_decode(str_replace('&nbsp;', ' ', strip_tags(stream_get_contents($f))));
                $text = mb_convert_encoding(file_get_contents($this->messageDir . '/files/' . $props['html']), 'UTF-8', 'ISO-8859-1');
                $this->assertStringEqualsIgnoreWhiteSpace($text, $str, $failMessage);
            }
            
            if (isset($props['To']['email'])) {
                $to = $message->getHeader('To');
                if (isset($props['To']['name'])) {
                    $this->assertEquals($props['To']['name'], $to->getPersonName(), $failMessage);
                }
                $this->assertEquals($props['To']['email'], $to->getValue(), $failMessage);
            }
            
            if (isset($props['From']['email'])) {
                $from = $message->getHeader('From');
                if (isset($props['From']['name'])) {
                    $this->assertNotNull($from, $failMessage);
                    $this->assertEquals($props['From']['name'], $from->getPersonName(), $failMessage);
                }
                $this->assertEquals($props['From']['email'], $from->getValue(), $failMessage);
            }
            
            if (isset($props['Subject'])) {
                $this->assertEquals($props['Subject'], $message->getHeaderValue('subject'), $failMessage);
            }
            
            if (!empty($props['attachments'])) {
                $this->assertEquals($props['attachments'], $message->getAttachmentCount(), $failMessage);
                $attachments = $message->getAllAttachmentParts();
                foreach ($attachments as $attachment) {
                    $name = $attachment->getHeaderParameter('Content-Type', 'name');
                    $file = file_get_contents($this->messageDir . '/files/' . $name);
                    $this->assertEquals($file, stream_get_contents($attachment->getContentResourceHandle()), $failMessage);
                }
            }
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
