<?php
namespace ZBateson\MailMimeParser\IntegrationTests;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;

/**
 * Description of EmailFunctionalTest
 *
 * @group Functional
 * @group EmailFunctionalTest
 * @covers ZBateson\MailMimeParser\Stream\Base64DecodeStreamFilter
 * @covers ZBateson\MailMimeParser\Stream\Base64EncodeStreamFilter
 * @covers ZBateson\MailMimeParser\Stream\CharsetStreamFilter
 * @covers ZBateson\MailMimeParser\Stream\ConvertStreamFilter
 * @covers ZBateson\MailMimeParser\Stream\UUDecodeStreamFilter
 * @covers ZBateson\MailMimeParser\Stream\UUEncodeStreamFilter
 * @covers ZBateson\MailMimeParser\Message
 * @covers ZBateson\MailMimeParser\MimePart
 * @author Zaahid Bateson
 */
class EmailFunctionalTest extends PHPUnit_Framework_TestCase
{
    private $parser;
    private $messageDir;
    
    protected function setUp()
    {
        $this->parser = new MailMimeParser();
        $this->messageDir = dirname(dirname(__DIR__)) . '/' . TEST_DATA_DIR . '/emails';
    }
    
    protected function assertStringEqualsIgnoreWhiteSpace($test, $str, $message = null)
    {
        $equal = (trim(preg_replace('/\s+/', ' ', $test)) === trim(preg_replace('/\s+/', ' ', $str)));
        if (!$equal) {
            file_put_contents(dirname(dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . "/fail_org", $test);
            file_put_contents(dirname(dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . "/fail_parsed", $str);
        }
        $this->assertTrue(
            $equal,
            $message . ' -- output written to _output/fail_org and _output/fail_parsed'
        );
    }
    
    protected function assertTextContentTypeEquals($expectedInputFileName, $actualInputStream, $message = null)
    {
        $str = stream_get_contents($actualInputStream);
        $text = mb_convert_encoding(file_get_contents($this->messageDir . '/files/' . $expectedInputFileName), 'UTF-8', 'ISO-8859-1');
        $this->assertStringEqualsIgnoreWhiteSpace($text, $str, $message);
    }
    
    protected function assertHtmlContentTypeEquals($expectedInputFileName, $actualInputStream, $message = null)
    {
        $str = html_entity_decode(str_replace('&nbsp;', ' ', strip_tags(stream_get_contents($actualInputStream))));
        $text = mb_convert_encoding(file_get_contents($this->messageDir . '/files/' . $expectedInputFileName), 'UTF-8', 'ISO-8859-1');
        $this->assertStringEqualsIgnoreWhiteSpace($text, $str, $message);
    }
    
    private function runEmailTestForMessage($message, array $props, $failMessage)
    {
        if (isset($props['text'])) {
            $f = $message->getTextStream();
            $this->assertNotNull($f, $failMessage);
            $this->assertTextContentTypeEquals($props['text'], $f, $failMessage);
        }

        if (isset($props['html'])) {
            $f = $message->getHtmlStream();
            $this->assertNotNull($f, $failMessage);
            $this->assertHtmlContentTypeEquals($props['html'], $f, $failMessage);
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
                if (empty($name)) {
                    $name = $attachment->getHeaderParameter('Content-Disposition', 'filename');
                }
                if (!empty($name) && file_exists($this->messageDir . '/files/' . $name)) {
                    
                    if ($attachment->getHeaderValue('Content-Type') === 'text/html') {
                        $this->assertHtmlContentTypeEquals(
                            $name,
                            $attachment->getContentResourceHandle(),
                            'HTML content is not equal'
                        );
                    } elseif (stripos($attachment->getHeaderValue('Content-Type'), 'text/') === 0) {
                        $this->assertTextContentTypeEquals(
                            $name,
                            $attachment->getContentResourceHandle(),
                            'Text content is not equal'
                        );
                    } else {
                        $file = file_get_contents($this->messageDir . '/files/' . $name);
                        $att = stream_get_contents($attachment->getContentResourceHandle());
                        $equal = ($file === $att);
                        if (!$equal) {
                            file_put_contents(dirname(dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . "/{$name}_fail_org", $file);
                            file_put_contents(dirname(dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . "/{$name}_fail_parsed", $att);
                        }
                        $this->assertTrue(
                            $equal,
                            $failMessage . " -- output written to _output/{$name}_fail_org and _output/{$name}_fail_parsed"
                        );
                    }
                }
            }
        }
    }
    
    private function runEmailTest($key, array $props) {
        $handle = fopen($this->messageDir . '/' . $key . '.txt', 'r');
        $message = $this->parser->parse($handle);
        fclose($handle);

        $failMessage = 'Failed while parsing ' . $key;
        $this->runEmailTestForMessage($message, $props, $failMessage);
        
        $tmpSaved = fopen(dirname(dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . "/$key", 'w+');
        $message->save($tmpSaved);
        rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved);
        fclose($tmpSaved);
        $failMessage = 'Failed while parsing saved message for ' . $key;
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }
    
    public function testParseEmailm0001()
    {
        $this->runEmailTest('m0001', [
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
        ]);
    }
    
    public function testParseEmailm0002()
    {
        $this->runEmailTest('m0002', [
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
        ]);
    }
    
    public function testParseEmailm0003()
    {
        $this->runEmailTest('m0003', [
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
        ]);
    }
    
    public function testParseEmailm0004()
    {
        $this->runEmailTest('m0004', [
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
        ]);
    }
    
    public function testParseEmailm0005()
    {
        $this->runEmailTest('m0005', [
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
        ]);
    }
    
    public function testParseEmailm0006()
    {
        $this->runEmailTest('m0006', [
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
        ]);
    }
    
    public function testParseEmailm0007()
    {
        $this->runEmailTest('m0007', [
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
        ]);
    }
    
    public function testParseEmailm0008()
    {
        $this->runEmailTest('m0008', [
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
        ]);
    }
    
    public function testParseEmailm0009()
    {
        $this->runEmailTest('m0009', [
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
        ]);
    }
    
    public function testParseEmailm0010()
    {
        $this->runEmailTest('m0010', [
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
        ]);
    }
    
    public function testParseEmailm0011()
    {
        $this->runEmailTest('m0011', [
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
        ]);
    }
    
    public function testParseEmailm0012()
    {
        $this->runEmailTest('m0012', [
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
        ]);
    }
    
    public function testParseEmailm0013()
    {
        $this->runEmailTest('m0013', [
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
        ]);
    }
    
    public function testParseEmailm0014()
    {
        $this->runEmailTest('m0014', [
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
        ]);
    }
    
    public function testParseEmailm0015()
    {
        $this->runEmailTest('m0015', [
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
        ]);
    }
    
    public function testParseEmailm0016()
    {
        $this->runEmailTest('m0016', [
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
        ]);
    }
    
    public function testParseEmailm0017()
    {
        $this->runEmailTest('m0017', [
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
        ]);
    }
    
    public function testParseEmailm0018()
    {
        $this->runEmailTest('m0018', [
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
            'attachments' => 3,
        ]);
    }
    
    public function testParseEmailm1001()
    {
        $this->runEmailTest('m1001', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
        ]);
    }
    
    public function testParseEmailm1002()
    {
        $this->runEmailTest('m1002', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
            'html' => 'HasenundFrФsche.txt',
        ]);
    }
    
    public function testParseEmailm1003()
    {
        $this->runEmailTest('m1003', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'text' => 'HasenundFrФsche.txt',
            'attachments' => 3,
        ]);
    }
    
    public function testParseEmailm1004()
    {
        $this->runEmailTest('m1004', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'text' => 'HasenundFrФsche.txt',
            'html' => 'HasenundFrФsche.txt',
            'attachments' => 2,
        ]);
    }
    
    public function testParseEmailm1005()
    {
        $this->runEmailTest('m1005', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'html' => 'HasenundFrФsche.txt',
            'attachments' => 4,
        ]);
    }
    
    public function testParseEmailm1006()
    {
        $this->runEmailTest('m1006', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'html' => 'HasenundFrФsche.txt',
            'attachments' => 4,
        ]);
    }
    
    public function testParseEmailm1007()
    {
        $this->runEmailTest('m1007', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'text' => 'hareandtortoise.txt'
        ]);
    }
    
    public function testParseEmailm1008()
    {
        $this->runEmailTest('m1008', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'text' => 'hareandtortoise.txt'
        ]);
    }
    
    public function testParseEmailm1009()
    {
        $this->runEmailTest('m1009', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'text' => 'hareandtortoise.txt',
            'attachments' => 3,
        ]);
    }
    
    /*
     * m1010.txt looks like it's badly encoded.  Was it really sent like that?
     */
    /*
    public function testParseEmailm1010()
    {
        $this->runEmailTest('m1010', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'text' => 'HasenundFrФsche.txt',
        ]);
    }*/
    
    /*
     * m1011.txt looks like it's badly encoded.  Was it really sent like that?
     */
    /*
    public function testParseEmailm1011()
    {
        $this->runEmailTest('m1011', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
        ]);
    }*/
    
    public function testParseEmailm1012()
    {
        $this->runEmailTest('m1012', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
        ]);
    }
    
    public function testParseEmailm1013()
    {
        $this->runEmailTest('m1013', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'attachments' => 1
        ]);
    }
    
    public function testParseEmailm1014()
    {
        $this->runEmailTest('m1014', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'text' => 'hareandtortoise.txt'
        ]);
    }
    
    public function testParseEmailm1015()
    {
        $this->runEmailTest('m1015', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'text' => 'hareandtortoise.txt',
            'attachments' => 1,
        ]);
    }
    
    public function testParseEmailm1016()
    {
        $this->runEmailTest('m1016', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from Netscape Communicator 4.7',
            'text' => 'hareandtortoise.txt',
            'attachments' => 1,
        ]);
    }
    
    public function testParseEmailm2001()
    {
        $this->runEmailTest('m2001', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'jschmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
        ]);
    }
    
    public function testParseEmailm2002()
    {
        $this->runEmailTest('m2002', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
            'html' => 'HasenundFrФsche.txt',
        ]);
    }
    
    public function testParseEmailm2003()
    {
        $this->runEmailTest('m2003', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'html' => 'HasenundFrФsche.txt',
        ]);
    }
    
    public function testParseEmailm2004()
    {
        $this->runEmailTest('m2004', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'html' => 'HasenundFrФsche.txt',
            'attachments' => 2,
        ]);
    }
    
    public function testParseEmailm2005()
    {
        $this->runEmailTest('m2005', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'html' => 'HasenundFrФsche.txt',
            // 'text' => 'HasenundFrФsche.txt', - contains extra text at the end
            'attachments' => 4,
        ]);
    }
    
    public function testParseEmailm2006()
    {
        $this->runEmailTest('m2006', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'html' => 'HasenundFrФsche.txt',
            'attachments' => 2,
        ]);
    }
    
    public function testParseEmailm2007()
    {
        $this->runEmailTest('m2007', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'html' => 'HasenundFrФsche.txt',
            'attachments' => 4,
        ]);
    }
    
    public function testParseEmailm2008()
    {
        $this->runEmailTest('m2008', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            //'text' => 'HasenundFrФsche.txt', contains extra text at the end
            'attachments' => 4,
        ]);
    }
    
    public function testParseEmailm2009()
    {
        $this->runEmailTest('m2009', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'html' => 'HasenundFrФsche.txt',
            'attachments' => 2,
        ]);
    }
    
    public function testParseEmailm2010()
    {
        $this->runEmailTest('m2010', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'jschmuergen@example.com'
            ],
            'Subject' => 'The Hare and the Tortoise',
            'text' => 'hareandtortoise.txt',
            'attachments' => 2,
        ]);
    }
    
    public function testParseEmailm2011()
    {
        $this->runEmailTest('m2011', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'jschmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
            //'attachments' => 2, - attachments are "binhex" encoded
        ]);
    }
    
    public function testParseEmailm2012()
    {
        $this->runEmailTest('m2012', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'jschmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
            'attachments' => 3,
        ]);
    }
    
    public function testParseEmailm2013()
    {
        $this->runEmailTest('m2013', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
            'attachments' => 2
        ]);
    }
    
    public function testParseEmailm2014()
    {
        $this->runEmailTest('m2014', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'jschmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt'
        ]);
    }
    
    public function testParseEmailm2015()
    {
        $this->runEmailTest('m2015', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
        ]);
    }
    
    public function testParseEmailm2016()
    {
        $this->runEmailTest('m2016', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'The Hare and the Tortoise',
            'text' => 'hareandtortoise.txt',
        ]);
    }
    
    public function testParseEmailm3001()
    {
        $this->runEmailTest('m3001', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@penguin.example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Test message from PINE',
            'attachments' => 2,
        ]);
    }
    
    public function testParseEmailm3002()
    {
        $this->runEmailTest('m3002', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@penguin.example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrФsche.txt',
        ]);
    }
    
    public function testParseEmailm3003()
    {
        $this->runEmailTest('m3003', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@penguin.example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'PNG graphic',
            'attachments' => 1,
        ]);
    }
    
    public function testParseEmailm3004()
    {
        $this->runEmailTest('m3004', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@penguin.example.com'
            ],
            'To' => [
                'name' => 'Joe Blow',
                'email' => 'blow@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche',
            // 'attachments' => 1, filename part is weird
        ]);
    }
    
    public function testParseEmailm3005()
    {
        $this->runEmailTest('m3005', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'jschmuergen@example.com'
            ],
            'Subject' => 'The Hare and the Tortoise',
            'text' => 'hareandtortoise.txt',
            'attachments' => 1
        ]);
    }
    
    public function testParseEmailm3006()
    {
        $this->runEmailTest('m3006', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'jschmuergen@example.com'
            ],
            'Subject' => 'The Hare and the Tortoise',
            'text' => 'hareandtortoise.txt',
            'attachments' => 1
        ]);
    }
    
    public function testRewriteEmailContentm0001()
    {
        $handle = fopen($this->messageDir . '/m0001.txt', 'r');
        $message = $this->parser->parse($handle);
        fclose($handle);

        $content = $message->getTextPart();
        $content->setRawHeader('Content-Type', "text/html;\r\n\tcharset=\"iso-8859-1\"");
        $test = '<span>This is my simple test</span>';
        $content->setContent($test);
        
        $tmpSaved = fopen(dirname(dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . "/rewrite_m0001", 'w+');
        $message->save($tmpSaved);
        rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved);
        fclose($tmpSaved);
        $c2 = $messageWritten->getHtmlPart();
        $this->assertEquals($test, $c2->getContent());
    }
    
    public function testRewriteEmailAttachmentm2004()
    {
        $handle = fopen($this->messageDir . '/m2004.txt', 'r');
        $message = $this->parser->parse($handle);
        fclose($handle);

        $att = $message->getAttachmentPart(0);
        $att->setRawHeader(
            'Content-Disposition',
            $att->getHeaderValue('Content-Disposition') . '; filename="greenball.png"'
        );
        $green = fopen($this->messageDir . '/files/greenball.png', 'r');
        $att->attachContentResourceHandle($green);
        
        $tmpSaved = fopen(dirname(dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . "/rewrite_m2004", 'w+');
        $message->save($tmpSaved);
        rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved);
        fclose($tmpSaved);
        $a2 = $messageWritten->getAttachmentPart(0);
        $this->assertEquals($a2->getHeaderParameter('Content-Disposition', 'filename'), 'greenball.png');
        $this->assertEquals(
            file_get_contents($this->messageDir . '/files/greenball.png'),
            $a2->getContent()
        );
    }
    
    public function testParseFromStringm0001()
    {
        $str = file_get_contents($this->messageDir . '/m0001.txt');
        $message = Message::from($str);
        $this->runEmailTestForMessage($message, [
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
        ], 'Failed to parse m0001 from a string');
    }
}
