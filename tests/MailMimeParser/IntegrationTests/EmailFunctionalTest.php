<?php

namespace ZBateson\MailMimeParser\IntegrationTests;

use DateTime;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\IMimePart;

/**
 * Description of EmailFunctionalTest
 *
 * @group Functional
 * @group EmailFunctionalTest
 * @author Zaahid Bateson
 */
class EmailFunctionalTest extends TestCase
{
    // useful for testing an actual signed message with external tools -- the
    // tests may actually fail with this set to true though, as it always
    // tries to sign rather than verify a signature
    public const USE_GPG_KEYGEN = false;

    private $parser;

    private $messageDir;

    protected function setUp() : void
    {
        $this->parser = new MailMimeParser(true);
        $this->messageDir = \dirname(\dirname(__DIR__)) . '/' . TEST_DATA_DIR . '/emails';
    }

    protected function assertStringEqualsIgnoreWhiteSpace($test, $str, $message = null)
    {
        $equal = (\trim(\preg_replace('/\s+/', ' ', $test)) === \trim(\preg_replace('/\s+/', ' ', $str)));
        if (!$equal) {
            \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/fail_org', $test);
            \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/fail_parsed', $str);
        }
        $this->assertTrue(
            $equal,
            $message . ' -- output written to _output/fail_org and _output/fail_parsed'
        );
    }

    protected function assertTextContentTypeEquals($expectedInputFileName, $actualInputStream, $message = null)
    {
        $str = $actualInputStream->getContents();
        $actualInputStream->rewind();
        $text = \mb_convert_encoding(\file_get_contents($this->messageDir . '/files/' . $expectedInputFileName), 'UTF-8', 'ISO-8859-1');
        $this->assertStringEqualsIgnoreWhiteSpace($text, $str, $message);
    }

    protected function assertHtmlContentTypeEquals($expectedInputFileName, $actualInputStream, $message = null)
    {
        $str = \html_entity_decode(\str_replace('&nbsp;', ' ', \strip_tags($actualInputStream->getContents())));
        $actualInputStream->rewind();
        $text = \mb_convert_encoding(\file_get_contents($this->messageDir . '/files/' . $expectedInputFileName), 'UTF-8', 'ISO-8859-1');
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

        if (isset($props['Date'])) {
            $this->assertNotNull($message->getHeader('date'));
            $this->assertEquals($props['Date'], $message->getHeader('date')->getDateTime(), $failMessage);
        }

        if (isset($props['Message-ID'])) {
            $this->assertNotNull($message->getHeaderValue('Message-ID'));
            $this->assertEquals($props['Message-ID'], $message->getHeaderValue('Message-ID'), $failMessage);
        }

        if (!empty($props['signed'])) {
            $this->assertEquals('multipart/signed', $message->getHeaderValue('Content-Type'), $failMessage);
            $protocol = $message->getHeaderParameter('Content-Type', 'protocol');
            $micalg = $message->getHeaderParameter('Content-Type', 'micalg');
            $signedPart = $message->getSignaturePart();
            $this->assertEquals($props['signed']['protocol'], $protocol, $failMessage);
            $this->assertEquals($props['signed']['micalg'], $micalg, $failMessage);
            $this->assertNotNull($signedPart, $failMessage);
            $signedPartProtocol = $props['signed']['protocol'];
            if (!empty($props['signed']['signed-part-protocol'])) {
                $signedPartProtocol = $props['signed']['signed-part-protocol'];
            }
            $this->assertEquals($signedPartProtocol, $signedPart->getHeaderValue('Content-Type'), $failMessage);
            $this->assertEquals(\trim($props['signed']['body']), \trim($signedPart->getContent()));
        }

        if (!empty($props['attachments'])) {
            $this->assertEquals($props['attachments'], $message->getAttachmentCount(), $failMessage);
            $attachments = $message->getAllAttachmentParts();
            foreach ($attachments as $attachment) {
                $name = $attachment->getFilename();
                if (!empty($name) && \file_exists($this->messageDir . '/files/' . $name)) {

                    if ($attachment->getContentType() === 'text/html') {
                        $this->assertHtmlContentTypeEquals(
                            $name,
                            $attachment->getContentStream(),
                            'HTML content is not equal'
                        );
                    } elseif ($attachment->isTextPart()) {
                        $this->assertTextContentTypeEquals(
                            $name,
                            $attachment->getContentStream(),
                            'Text content is not equal'
                        );
                    } else {
                        $file = \file_get_contents($this->messageDir . '/files/' . $name);
                        $handle = $attachment->getBinaryContentResourceHandle();
                        $att = \stream_get_contents($handle);
                        \rewind($handle);
                        $equal = ($file === $att);
                        if (!$equal) {
                            \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . "/{$name}_fail_org", $file);
                            \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . "/{$name}_fail_parsed", $att);
                        }
                        $this->assertTrue(
                            $equal,
                            $failMessage . " -- output written to _output/{$name}_fail_org and _output/{$name}_fail_parsed"
                        );
                    }
                }
            }
        }
        if (!empty($props['parts'])) {
            $this->runPartsTests($message, $props['parts'], $failMessage);
        }
    }

    private function runPartsTests($part, array $types, $failMessage)
    {
        $this->assertNotNull($part, $failMessage);
        $this->assertNotNull($types);
        foreach ($types as $key => $type) {
            if (\is_array($type)) {
                $this->assertEquals(
                    \strtolower($key),
                    $part->getContentType(),
                    $failMessage
                );
                $this->assertInstanceOf('ZBateson\MailMimeParser\Message\IMimePart', $part);
                $cparts = $part->getChildParts();
                $curPart = \current($cparts);
                $this->assertCount(\count($type), $cparts, $failMessage);
                foreach ($type as $key => $ctype) {
                    $this->runPartsTests($curPart, [$key => $ctype], $failMessage);
                    $curPart = \next($cparts);
                }
            } else {
                if ($part instanceof IMimePart) {
                    $this->assertEmpty($part->getChildParts(), $failMessage);
                }
                $this->assertEquals(
                    \strtolower($type),
                    \strtolower($part->getContentType()),
                    $failMessage
                );
            }
        }
    }

    private function runEmailTest($key, array $props) {
        $handle = \fopen($this->messageDir . '/' . $key . '.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $failMessage = 'Failed while parsing ' . $key;
        $this->runEmailTestForMessage($message, $props, $failMessage);

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . "/$key", 'w+');

        $parts = $message->getAllParts();
        foreach ($parts as $part) {
            $part->notify();
        }

        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for ' . $key;
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    private function getSignatureForContent($signableContent)
    {
        if (static::USE_GPG_KEYGEN) {
            $command = 'gpg --sign --detach-sign --armor --cipher-algo AES256 --digest-algo SHA256 --textmode --lock-never';
            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'r']
            ];
            $cwd = \sys_get_temp_dir();
            $proc = \proc_open($command, $descriptorspec, $pipes, $cwd);
            \fwrite($pipes[0], $signableContent);
            \fclose($pipes[0]);
            $signature = \trim(\stream_get_contents($pipes[1]));
            \fclose($pipes[1]);
            \fclose($pipes[2]);
            \proc_close($proc);
            return \preg_replace('/\r|\n/', '', $signature);
        }
            return \md5($signableContent);

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
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'Message-ID' => 'NDBBIAKOPKHFGPLCODIGIEKBCHAA.doug@example.com',
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
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
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'Message-ID' => 'NDBBIAKOPKHFGPLCODIGMEKBCHAA.doug@example.com',
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
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
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'Message-ID' => 'NDBBIAKOPKHFGPLCODIGAEKCCHAA.doug@example.com',
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
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
            'Message-ID' => 'NDBBIAKOPKHFGPLCODIGEEKCCHAA.doug@example.com',
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
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
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
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
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
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
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
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
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
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
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
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
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
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
            'attachments' => 3,
            'parts' => [
                'multipart/mixed' => [
                    'text/plain',
                    'image/png',
                    'image/png',
                    'image/png'
                ]
            ],
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
            'attachments' => 1,
            'parts' => [
                'image/png',
            ],
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
            'attachments' => 2,
            'parts' => [
                'multipart/mixed' => [
                    'image/png',
                    'image/png',
                ]
            ],
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
            'parts' => [
                'multipart/alternative' => [
                    'text/plain',
                    'text/html'
                ]
            ]
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
            'parts' => [
                'multipart/mixed' => [
                    'multipart/alternative' => [
                        'text/plain',
                        'text/html'
                    ],
                    'image/png',
                    'image/png'
                ]
            ]
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
            'parts' => [
                'multipart/related' => [
                    'multipart/alternative' => [
                        'text/plain',
                        'text/html'
                    ],
                    'image/png',
                    'image/png'
                ]
            ]
        ]);
    }

    public function testGetAttachmentByContentIdFromEmailm0016()
    {
        $handle = \fopen($this->messageDir . '/m0016.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $part = $message->getPartByContentId('823504223@17052000-0f8d');
        $this->assertNotNull($part);
        $this->assertEquals('823504223@17052000-0f8d', $part->getContentId());
        $this->assertEquals('blueball.png', $part->getFilename());

        $other = $message->getPartByContentId('<823504223@17052000-0f94>');
        $this->assertNotNull($other);
        $this->assertEquals('823504223@17052000-0f94', $other->getContentId());
        $this->assertEquals('redball.png', $other->getFilename());
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
            'parts' => [
                'multipart/mixed' => [
                    'multipart/related' => [
                        'multipart/alternative' => [
                            'text/plain',
                            'text/html'
                        ],
                        'image/png'
                    ],
                    'image/png',
                    'image/png'
                ]
            ]
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
            'parts' => [
                'text/plain' => [
                    'application/octet-stream',
                    'application/octet-stream',
                    'application/octet-stream'
                ]
            ]
        ]);
    }

    public function testParseEmailm0019()
    {
        $this->runEmailTest('m0019', [
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

    public function testParseEmailm0020()
    {
        $this->runEmailTest('m0020', [
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

    public function testParseEmailm0021()
    {
        $this->runEmailTest('m0021', [
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
            'attachments' => 3,
            'parts' => [
                'multipart/mixed' => [
                    'text/plain',
                    'image/png',
                    'image/png',
                    'image/png'
                ]
            ],
        ]);
    }

    public function testParseEmailm0022()
    {
        $this->runEmailTest('m0022', [
            'From' => [
                'email' => 'test@test.sk'
            ],
            'Message-ID' => 'dcf0f7ab-4210-4ee7-7ac3-3db26735f7b1@gmail.com',
            'Subject' => 'long att',
            'attachments' => 1,
            'parts' => [
                'multipart/mixed' => [
                    'text/html',
                    'text/plain'
                ]
            ],
        ]);
    }

    public function testParseEmailm0023()
    {
        $this->runEmailTest('m0023', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Date' => new DateTime('13 Mar 2003 12:44:07 -0500'),
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
        ]);
    }

    public function testParseEmailm0024()
    {
        $this->runEmailTest('m0024', [
            'From' => [
                'name' => 'John DOE',
                'email' => 'blablafakeemail@provider.fr'
            ],
            'To' => [
                'name' => 'list-name',
                'email' => 'list-name@list-domain.org'
            ],
            'Date' => new DateTime('21 Jul 2014 17:57:01 +0200'),
            'Subject' => 'Persil, abeilles ...',
            'parts' => [
                'multipart/mixed' => [
                    'text/plain',
                    'application/msword'
                ]
            ],
            'attachments' => 1,
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
            'Message-ID' => '3923561C.B7078DEF@example.com',
            'Subject' => 'Die Hasen und die Frösche (Netscape Communicator 4.7)',
            'text' => 'HasenundFrosche.txt',
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
            'Subject' => 'Die Hasen und die Frösche (Netscape Communicator 4.7)',
            'text' => 'HasenundFrosche.txt',
            'html' => 'HasenundFrosche.txt',
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
            'text' => 'HasenundFrosche.txt',
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
            'text' => 'HasenundFrosche.txt',
            'html' => 'HasenundFrosche.txt',
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
            'Subject' => 'Die Hasen und die Frösche (Netscape Messenger 4.7)',
            'html' => 'HasenundFrosche.txt',
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
            'html' => 'HasenundFrosche.txt',
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
            'attachments' => 2,
        ]);
    }

    // m1010.txt the encoding is wrong, using setCharsetOverride
    public function testParseEmailm1010()
    {
        $handle = \fopen($this->messageDir . '/m1010.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $failMessage = 'Failed while parsing m1010';
        $message->setCharsetOverride('iso-8859-1');
        $f = $message->getTextStream(0);
        $this->assertNotNull($f, $failMessage);
        $this->assertTextContentTypeEquals('HasenundFrosche.txt', $f, $failMessage);

        $message = null;
        // still open
        \fseek($handle, 0);
        \fread($handle, 1);
        \fclose($handle);
    }

    // m1011.txt utf-7 encoded, needs imap_mutf7_to_utf8 or similar
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
            'text' => 'HasenundFrosche.txt',
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
            'text' => 'HasenundFrosche.txt',
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
            'text' => 'hareandtortoise.txt',
            'attachments' => 3
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
            'text' => 'hareandtortoise.txt'
        ]);
        $handle = \fopen($this->messageDir . '/m1015.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $stream = $message->getTextStream(1);
        $this->assertTextContentTypeEquals('HasenundFrosche.txt', $stream);
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
        ]);
        $handle = \fopen($this->messageDir . '/m1016.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $stream = $message->getTextStream(1);
        $str = $this->assertTextContentTypeEquals('farmerandstork.txt', $stream);
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
            'Message-ID' => '4.2.0.58.20000519001217.00a85b60@pop.example.com',
            'Subject' => 'Die Hasen und die Frösche',
            'text' => 'HasenundFrosche.txt',
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
            'text' => 'HasenundFrosche.txt',
            'html' => 'HasenundFrosche.txt',
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
            'html' => 'HasenundFrosche.txt',
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
            'html' => 'HasenundFrosche.txt',
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
            'html' => 'HasenundFrosche.txt',
            // 'text' => 'HasenundFrosche.txt', - contains extra text at the end
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
            'html' => 'HasenundFrosche.txt',
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
            'html' => 'HasenundFrosche.txt',
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
            //'text' => 'HasenundFrosche.txt', contains extra text at the end
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
            'html' => 'HasenundFrosche.txt',
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
            'text' => 'HasenundFrosche.txt',
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
            'text' => 'HasenundFrosche.txt',
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
            'text' => 'HasenundFrosche.txt',
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
            'text' => 'HasenundFrosche.txt'
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
            'text' => 'HasenundFrosche.txt',
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
            'text' => 'HasenundFrosche.txt',
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
            'attachments' => 1,
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

    public function testParseEmailm3007()
    {
        $this->runEmailTest('m3007', [
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
            'attachments' => 3,
        ]);
    }

    public function testParseFromStringm0001()
    {
        $str = \file_get_contents($this->messageDir . '/m0001.txt');
        $message = Message::from($str, false);
        $this->runEmailTestForMessage($message, [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrosche.txt'
        ], 'Failed to parse m0001 from a string');
    }

    public function testVerifySignedEmailm4001()
    {
        $handle = \fopen($this->messageDir . '/m4001.txt', 'r');
        $message = $this->parser->parse($handle, false);

        $testString = $message->getSignedMessageAsString();
        $this->assertEquals(\md5($testString), \trim($message->getSignaturePart()->getContent()));
        \fclose($handle);
    }

    public function testParseEmailm4001()
    {
        $this->runEmailTest('m4001', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrosche.txt',
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => '9825cba003a7ac85b9a3f3dc9f8423fd'
            ],
        ]);
    }

    public function testVerifySignedEmailm4002()
    {
        $handle = \fopen($this->messageDir . '/m4002.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $testString = $message->getSignedMessageAsString();
        $this->assertEquals(\md5($testString), \trim($message->getSignaturePart()->getContent()));
    }

    public function testParseEmailm4002()
    {
        $this->runEmailTest('m4002', [
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
            'attachments' => 3,
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'md5',
                'body' => 'f691886408cbeedc753548d2d198bf92'
            ],
        ]);
    }

    public function testVerifySignedEmailm4003()
    {
        $stream = Utils::streamFor(\fopen($this->messageDir . '/m4003.txt', 'r'));
        $message = $this->parser->parse($stream, true);

        $testString = $message->getSignedMessageAsString();
        $this->assertEquals(\md5($testString), \trim($message->getSignaturePart()->getContent()));
    }

    public function testParseEmailm4003()
    {
        $this->runEmailTest('m4003', [
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
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => 'ba0ce5fac600d1a2e1f297d0040b858c'
            ],
        ]);
    }

    public function testVerifySignedEmailm4004()
    {
        $handle = \fopen($this->messageDir . '/m4004.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $testString = $message->getSignedMessageAsString();
        $this->assertEquals(\md5($testString), \trim($message->getSignaturePart()->getContent()));
    }

    public function testParseEmailm4004()
    {
        $this->runEmailTest('m4004', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Netscape Messenger 4.7)',
            'html' => 'HasenundFrosche.txt',
            'attachments' => 4,
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => 'eb4c0347d13a2bf71a3f9673c4b5e3db'
            ],
        ]);
    }

    public function testParseEmailm4005()
    {
        $handle = \fopen($this->messageDir . '/m4005.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $str = \file_get_contents($this->messageDir . '/files/blueball.png');
        $this->assertEquals(1, $message->getAttachmentCount());
        $this->assertEquals('text/rtf', $message->getAttachmentPart(0)->getHeaderValue('Content-Type'));
        $this->assertSame($message->getAttachmentPart(0)->getContent(), $str, 'text/rtf stream doesn\'t match binary stream');

        $props = [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Test message from Microsoft Outlook 00',
            'text' => 'hareandtortoise.txt'
        ];

        $this->runEmailTestForMessage($message, $props, 'failed parsing m4005');
        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/m4005', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for adding a large attachment to m0001';
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);

        $this->assertEquals(1, $messageWritten->getAttachmentCount());
        $this->assertEquals('text/rtf', $messageWritten->getAttachmentPart(0)->getHeaderValue('Content-Type'));
        $this->assertSame($messageWritten->getAttachmentPart(0)->getContent(), $str, 'text/rtf stream doesn\'t match binary stream');
    }

    public function testParseEmailm4006()
    {
        $this->runEmailTest('m4006', [
            'From' => [
                'name' => 'Test Sender',
                'email' => 'sender@email.test'
            ],
            'To' => [
                'name' => 'Test Recipient',
                'email' => 'recipient@email.test'
            ],
            'Subject' => 'Read: invitation',
            'attachments' => 1,
        ]);
    }

    public function testParseEmailm4007()
    {
        $this->runEmailTest('m4007', [
            'From' => [
                'name' => 'Test Sender',
                'email' => 'sender@email.test'
            ],
            'To' => [
                'name' => 'Test Recipient',
                'email' => 'recipient@email.test'
            ],
            'Subject' => 'Test multipart-digest',
            'attachments' => 1,
        ]);
    }

    public function testVerifySignedEmailm4008()
    {
        $handle = \fopen($this->messageDir . '/m4008.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $testString = $message->getSignedMessageAsString();
        $this->assertEquals(\md5($testString), \trim($message->getSignaturePart()->getContent()));
    }

    public function testParseEmailm4008()
    {
        $this->runEmailTest('m4008', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Netscape Messenger 4.7)',
            'signed' => [
                'protocol' => 'application/x-pgp-signature',
                'signed-part-protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => '9f5c560f86b607c9087b84e9baa98189'
            ],
        ]);
    }

    public function testParseEmailGitHub_102_01()
    {
        $this->runEmailTest('github-102-01', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'Message-ID' => 'NDBBIAKOPKHFGPLCODIGIEKBCHAA.doug@example.com',
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
        ]);
    }

    public function testParseEmailGitHub_102_02()
    {
        $this->runEmailTest('github-102-02', [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'Message-ID' => 'NDBBIAKOPKHFGPLCODIGIEKBCHAA.doug@example.com',
            'text' => 'HasenundFrosche.txt',
            'parts' => [
                'text/plain'
            ],
        ]);
    }

    public function testParseEmailGitHub_115()
    {
        $this->runEmailTest('github-115', [
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
            'attachments' => 3,
            'parts' => [
                'multipart/*' => [
                    'text/plain',
                    'image/png',
                    'image/png',
                    'image/png'
                ]
            ],
        ]);
    }

    public function testRewriteEmailContentm0001()
    {
        $handle = \fopen($this->messageDir . '/m0001.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $content = $message->getTextPart();
        $content->setRawHeader('Content-Type', "text/html;\r\n\tcharset=\"iso-8859-1\"");
        $test = '<span>This is my simple test</span>';
        $content->setContent($test);

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/rewrite_m0001', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $c2 = $messageWritten->getHtmlPart();
        $this->assertEquals($test, $c2->getContent());
    }

    public function testRewriteEmailAttachmentm2004()
    {
        $handle = \fopen($this->messageDir . '/m2004.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $att = $message->getAttachmentPart(0);
        $att->setRawHeader(
            'Content-Disposition',
            $att->getHeaderValue('Content-Disposition') . '; filename="greenball.png"'
        );
        $green = \fopen($this->messageDir . '/files/greenball.png', 'r');
        $att->attachContentStream(Utils::streamFor($green));

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/rewrite_m2004', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $a2 = $messageWritten->getAttachmentPart(0);
        $this->assertEquals('greenball.png', $a2->getHeaderParameter('Content-Disposition', 'filename'));
        $this->assertEquals(
            \file_get_contents($this->messageDir . '/files/greenball.png'),
            $a2->getContent()
        );
    }

    public function testRemoveAttachmentPartm0013()
    {
        $handle = \fopen($this->messageDir . '/m0013.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $props = [
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
        ];

        $message->removeAttachmentPart(0);

        $test1 = $props;
        $test1['attachments'] = 1;

        $this->assertEquals(1, $message->getAttachmentCount());
        $att = $message->getAttachmentPart(0);
        $this->assertEquals('redball.png', $att->getHeaderParameter('Content-Disposition', 'filename'));
        $this->runEmailTestForMessage($message, $test1, 'failed removing content parts from m0013');
    }

    public function testRemoveContentPartsm0014()
    {
        $handle = \fopen($this->messageDir . '/m0014.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $message->removeTextPart();

        $props = [
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
        ];

        $test1 = $props;
        unset($test1['text']);
        $this->assertNull($message->getTextPart());
        $this->runEmailTestForMessage($message, $test1, 'failed removing content parts from m0014');
    }

    public function testRemoveTextPartm0020()
    {
        $handle = \fopen($this->messageDir . '/m0020.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $props = [
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
        ];

        $test1 = $props;
        unset($test1['text']);

        $message->removeTextPart();
        $this->assertNull($message->getTextPart());
        $this->runEmailTestForMessage($message, $test1, 'failed removing text part from m0020');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/rm_m0020', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for rm_m0020';
        $this->runEmailTestForMessage($messageWritten, $test1, $failMessage);
    }

    public function testRemoveAllHtmlPartsm0020()
    {
        $handle = \fopen($this->messageDir . '/m0020.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $props = [
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
        ];

        $test1 = $props;
        unset($test1['html']);

        $message->removeAllHtmlParts();
        $this->assertNull($message->getHtmlPart());
        $this->runEmailTestForMessage($message, $test1, 'failed removing content parts from m0020');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/rmh_m0020', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for rmh_m0020';
        $this->runEmailTestForMessage($messageWritten, $test1, $failMessage);
    }

    public function testRemoveHtmlPartm0020()
    {
        $handle = \fopen($this->messageDir . '/m0020.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $props = [
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
        ];

        $test1 = $props;
        unset($test1['html']);

        $firstHtmlPart = $message->getHtmlPart();
        $secondHtmlPart = $message->getHtmlPart(1);
        $thirdHtmlPart = $message->getHtmlPart(2);

        $secondContent = $secondHtmlPart->getContent();

        $message->removeHtmlPart();
        $this->assertNotNull($message->getHtmlPart());
        $this->assertNotEquals($firstHtmlPart, $message->getHtmlPart());
        $this->assertEquals($secondHtmlPart, $message->getHtmlPart());
        $this->assertEquals($thirdHtmlPart, $message->getHtmlPart(1));
        $message->removeHtmlPart(1);
        $this->assertEquals($secondHtmlPart, $message->getHtmlPart());
        $this->assertNull($message->getHtmlPart(1));
        $this->runEmailTestForMessage($message, $test1, 'failed removing html content parts from m0020');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/rmho_m0020', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for rmho_m0020';
        $this->runEmailTestForMessage($messageWritten, $test1, $failMessage);

        $this->assertNotNull($messageWritten->getHtmlPart());
        $this->assertEquals($secondContent, $messageWritten->getHtmlContent());

        $this->assertNotNull($message->getPartByMimeType('multipart/alternative'));
        $message->removeHtmlPart();
        $this->assertNull($message->getHtmlPart());
        $this->assertNull($message->getPartByMimeType('multipart/alternative'));
        $tmpSaved2 = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/rmha_m0020', 'w+');
        $message->save($tmpSaved2);
        \rewind($tmpSaved2);

        $messageWritten2 = $this->parser->parse($tmpSaved2, true);
        $failMessage = 'Failed while parsing saved message for rmha_m0020';
        $this->runEmailTestForMessage($messageWritten2, $test1, $failMessage);

        $this->assertNotNull($messageWritten->getHtmlPart());
        $this->assertEquals($secondContent, $messageWritten->getHtmlContent());
    }

    public function testAddHtmlPartRemoveTextPartm0001()
    {
        $handle = \fopen($this->messageDir . '/m0001.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $str = $message->getTextContent();
        $message->setHtmlPart($str, 'utf8');
        $this->assertNotNull($message->getTextPart());
        $this->assertNotNull($message->getHtmlPart());

        $message->removeTextPart();
        $this->assertNotNull($message->getHtmlPart());
        $this->assertNull($message->getTextPart());

        $props = [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'html' => 'HasenundFrosche.txt'
        ];

        $this->runEmailTestForMessage($message, $props, 'failed adding html part and removing text part from m0001');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/apr_m0001', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for m0001';
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testRemoveContentAndAttachmentPartsm0015()
    {
        $handle = \fopen($this->messageDir . '/m0015.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $message->removeHtmlPart();
        $this->assertNull($message->getHtmlPart());
        $this->assertEquals(2, $message->getAttachmentCount());
        $message->removeAttachmentPart(0);
        $this->assertEquals(1, $message->getAttachmentCount());

        $props = [
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
        ];

        $test1 = $props;
        unset($test1['html']);
        $test1['attachments'] = 1;
        $att = $message->getAttachmentPart(0);
        $this->assertEquals('redball.png', $att->getHeaderParameter('Content-Disposition', 'filename'));

        $this->runEmailTestForMessage($message, $test1, 'failed removing content parts from m0015');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/rm_m0015', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for m0015';
        $this->assertNull($messageWritten->getHtmlPart());
        $this->runEmailTestForMessage($messageWritten, $test1, $failMessage);
    }

    public function testAddHtmlContentPartm0001()
    {
        $handle = \fopen($this->messageDir . '/m0001.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $this->assertNull($message->getHtmlPart());
        $this->assertNotNull($message->getTextPart());
        $message->setHtmlPart(\file_get_contents($this->messageDir . '/files/hareandtortoise.txt'));
        $this->assertNotNull($message->getHtmlPart());
        $this->assertNotNull($message->getTextPart());

        $props = [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrosche.txt',
            'html' => 'hareandtortoise.txt',
        ];

        $this->runEmailTestForMessage($message, $props, 'failed adding HTML part to m0001');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/add_m0001', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added HTML content to m0001';
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testAddTextAndHtmlContentPartm0013()
    {
        $handle = \fopen($this->messageDir . '/m0013.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $this->assertNull($message->getHtmlPart());
        $this->assertNull($message->getTextPart());
        $message->setTextPart(\file_get_contents($this->messageDir . '/files/hareandtortoise.txt'));
        $message->setHtmlPart(\file_get_contents($this->messageDir . '/files/hareandtortoise.txt'));

        $props = [
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
            'attachments' => 2
        ];

        $this->assertNotNull($message->getHtmlPart());
        $this->assertNotNull($message->getTextPart());
        $this->runEmailTestForMessage($message, $props, 'failed adding HTML and Text parts to m0013');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/add_m0013', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added HTML content to m0013';
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testAddTextAndHtmlContentPartm0018()
    {
        $handle = \fopen($this->messageDir . '/m0018.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $this->assertNull($message->getHtmlPart());
        $message->setHtmlPart(\file_get_contents($this->messageDir . '/files/hareandtortoise.txt'));
        $this->assertTrue($message->isMime());
        $this->assertNotNull($message->getTextPart());
        $this->assertNotNull($message->getHtmlPart());

        $props = [
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
        ];

        $this->assertNotNull($message->getHtmlPart());
        $this->runEmailTestForMessage($message, $props, 'failed adding HTML part to m0018');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/add_m0018', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added HTML content to m0018';
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testAddAttachmentPartm0001()
    {
        $handle = \fopen($this->messageDir . '/m0001.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $message->addAttachmentPart(
            \file_get_contents($this->messageDir . '/files/blueball.png'),
            'image/png',
            'blueball.png'
        );

        $props = [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrosche.txt',
            'attachments' => 1,
        ];

        $this->runEmailTestForMessage($message, $props, 'failed adding attachment part to m0001');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/att_m0001', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added attachment to m0001';
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);

        $message->addAttachmentPartFromFile(
            $this->messageDir . '/files/redball.png',
            'image/png'
        );
        $props['attachments'] = 2;

        $this->runEmailTestForMessage($message, $props, 'failed adding second attachment part to m0001');

        $tmpSaved2 = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/att2_m0001', 'w+');
        $message->save($tmpSaved2);
        \rewind($tmpSaved2);

        $messageWritten = $this->parser->parse($tmpSaved2, true);
        $failMessage = 'Failed while parsing saved message for second added attachment to m0001';
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testAddAttachmentPartQuotedPrintablem0001()
    {
        $handle = \fopen($this->messageDir . '/m0001.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $message->addAttachmentPart(
            \file_get_contents($this->messageDir . '/files/farmerandstork.txt'),
            'text/plain',
            'farmerandstork.txt',
            'attachment',
            'quoted-printable'
        );

        $props = [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrosche.txt',
            'attachments' => 1,
        ];

        $this->runEmailTestForMessage($message, $props, 'failed adding attachment part to m0001');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/attqp_m0001', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added attachment to m0001';
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);

        $message->addAttachmentPartFromFile(
            $this->messageDir . '/files/hareandtortoise.txt',
            'text/plain',
            'hareandtortoise.txt',
            'attachment',
            '8bit'
        );
        $props['attachments'] = 2;

        $this->runEmailTestForMessage($message, $props, 'failed adding second attachment part to m0001');

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/att8bit_m0001', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for second added attachment to m0001';
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testAddLargeAttachmentPartm0001()
    {
        $handle = \fopen($this->messageDir . '/m0001.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $message->addAttachmentPartFromFile(
            $this->messageDir . '/files/bin-bashy.jpg',
            'image/jpeg'
        );

        $props = [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrosche.txt',
            'attachments' => 1,
        ];

        $this->runEmailTestForMessage($message, $props, 'failed adding large attachment part to m0001');
        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/attl_m0001', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for adding a large attachment to m0001';
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testCreateSignedPartm0001()
    {
        $handle = \fopen($this->messageDir . '/m0001.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $this->assertNull($message->getHtmlPart());
        $message->setAsMultipartSigned('pgp-sha256', 'application/pgp-signature');

        $signableContent = $message->getSignedMessageAsString();
        //$signature = md5($signableContent);

        \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sigpart_m0001', $signableContent);
        $signature = $this->getSignatureForContent($signableContent);

        $message->setSignature($signature);

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sig_m0001', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $this->assertNotEmpty($signableContent);
        $this->assertNotFalse(\strpos(
            \preg_replace('/\r\n|\r|\n/', "\r\n", \stream_get_contents($tmpSaved)),
            $signableContent
        ));
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added HTML content to m0001';

        $testString = $messageWritten->getSignedMessageAsString();
        $this->assertEquals($signableContent, $testString);
        $this->assertEquals($this->getSignatureForContent($testString), $signature);

        $props = [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'doug@example.com'
            ],
            'To' => [
                'name' => 'Jürgen Schmürgen',
                'email' => 'schmuergen@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Microsoft Outlook 00)',
            'text' => 'HasenundFrosche.txt',
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => $signature
            ]
        ];
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testCreateSignedPartm0014()
    {
        $handle = \fopen($this->messageDir . '/m0014.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $message->setAsMultipartSigned('pgp-sha256', 'application/pgp-signature');

        $this->assertEquals('text/html', $message->getHtmlPart()->getHeaderValue('Content-Type'));
        $signableContent = $message->getSignedMessageAsString();

        \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sigpart_m0014', $signableContent);
        $signature = $this->getSignatureForContent($signableContent);
        $message->setSignature($signature);

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sig_m0014', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $this->assertTrue(\strpos(\preg_replace('/\r\n|\r|\n/', "\r\n", \stream_get_contents($tmpSaved)), $signableContent) !== false);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added HTML content to m0014';

        $testString = $messageWritten->getSignedMessageAsString();
        $this->assertEquals($this->getSignatureForContent($testString), $signature);

        $props = [
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
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => $signature
            ]
        ];

        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testCreateSignedPartm0015()
    {
        $handle = \fopen($this->messageDir . '/m0015.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $message->setAsMultipartSigned('pgp-sha256', 'application/pgp-signature');

        $this->assertEquals(2, $message->getChildCount());
        $this->assertEquals('multipart/mixed', \strtolower($message->getChild(0)->getHeaderValue('Content-Type')));

        $signableContent = $message->getSignedMessageAsString();
        \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sigpart_m0015', $signableContent);
        $signature = $this->getSignatureForContent($signableContent);
        $message->setSignature($signature);

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sig_m0015', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $this->assertTrue(\strpos(\preg_replace('/\r\n|\r|\n/', "\r\n", \stream_get_contents($tmpSaved)), $signableContent) !== false);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added HTML content to m0015';

        $testString = $messageWritten->getSignedMessageAsString();
        $this->assertEquals($this->getSignatureForContent($testString), $signature);

        $props = [
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
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => $signature
            ]
        ];

        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testCreateSignedPartm0018()
    {
        $handle = \fopen($this->messageDir . '/m0018.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $this->assertNull($message->getHtmlPart());
        $message->setAsMultipartSigned('pgp-sha256', 'application/pgp-signature');
        $signableContent = $message->getSignedMessageAsString();

        \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sigpart_m0018', $signableContent);
        $signature = $this->getSignatureForContent($signableContent);
        $message->setSignature($signature);

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sig_m0018', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $this->assertTrue(\strpos(\preg_replace('/\r\n|\r|\n/', "\r\n", \stream_get_contents($tmpSaved)), $signableContent) !== false);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added HTML content to m0018';

        $testString = $messageWritten->getSignedMessageAsString();
        $this->assertEquals($this->getSignatureForContent($testString), $signature);

        $props = [
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
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => $signature
            ]
        ];
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testCreateSignedPartm0019()
    {
        $handle = \fopen($this->messageDir . '/m0019.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $this->assertNotNull($message->getHtmlPart());
        $message->setAsMultipartSigned('pgp-sha256', 'application/pgp-signature');
        $signableContent = $message->getSignedMessageAsString();

        \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sigpart_m0019', $signableContent);
        $signature = $this->getSignatureForContent($signableContent);
        $message->setSignature($signature);

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sig_m0019', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $this->assertTrue(\strpos(\preg_replace('/\r\n|\r|\n/', "\r\n", \stream_get_contents($tmpSaved)), $signableContent) !== false);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added HTML content to signed part sig_m0019';

        $testString = $messageWritten->getSignedMessageAsString();
        $this->assertEquals($this->getSignatureForContent($testString), $signature);

        $props = [
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
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => $signature
            ]
        ];
        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testCreateSignedPartm1005()
    {
        $handle = \fopen($this->messageDir . '/m1005.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $message->setAsMultipartSigned('pgp-sha256', 'application/pgp-signature');
        $signableContent = $message->getSignedMessageAsString();

        \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sigpart_m1005', $signableContent);
        $signature = $this->getSignatureForContent($signableContent);
        $message->setSignature($signature);

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sig_m1005', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $this->assertTrue(\strpos(\preg_replace('/\r\n|\r|\n/', "\r\n", \stream_get_contents($tmpSaved)), $signableContent) !== false);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for added HTML content to m1005';

        $testString = $messageWritten->getSignedMessageAsString();
        $this->assertEquals($this->getSignatureForContent($testString), $signature);

        $props = [
            'From' => [
                'name' => 'Doug Sauder',
                'email' => 'dwsauder@example.com'
            ],
            'To' => [
                'name' => 'Heinz Müller',
                'email' => 'mueller@example.com'
            ],
            'Subject' => 'Die Hasen und die Frösche (Netscape Messenger 4.7)',
            'html' => 'HasenundFrosche.txt',
            'attachments' => 4,
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => $signature
            ]
        ];

        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testCreateSignedPartForEmailm4006()
    {
        $handle = \fopen($this->messageDir . '/m4006.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $message->setAsMultipartSigned('pgp-sha256', 'application/pgp-signature');

        $signableContent = $message->getSignedMessageAsString();
        \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sigpart_m4006', $signableContent);
        $signature = $this->getSignatureForContent($signableContent);
        $message->setSignature($signature);

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sig_m4006', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $this->assertTrue(\strpos(\preg_replace('/\r\n|\r|\n/', "\r\n", \stream_get_contents($tmpSaved)), $signableContent) !== false);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for m4006';

        $testString = $messageWritten->getSignedMessageAsString();
        $this->assertEquals($this->getSignatureForContent($testString), $signature);

        $props = [
            'From' => [
                'name' => 'Test Sender',
                'email' => 'sender@email.test'
            ],
            'To' => [
                'name' => 'Test Recipient',
                'email' => 'recipient@email.test'
            ],
            'Subject' => 'Read: invitation',
            'attachments' => 1,
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => $signature
            ]
        ];

        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testCreateSignedPartForEmailm4007()
    {
        $handle = \fopen($this->messageDir . '/m4007.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $message->setAsMultipartSigned('pgp-sha256', 'application/pgp-signature');

        $signableContent = $message->getSignedMessageAsString();
        \file_put_contents(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sigpart_m4007', $signableContent);
        $signature = $this->getSignatureForContent($signableContent);
        $message->setSignature($signature);

        $tmpSaved = \fopen(\dirname(\dirname(__DIR__)) . '/' . TEST_OUTPUT_DIR . '/sig_m4007', 'w+');
        $message->save($tmpSaved);
        \rewind($tmpSaved);

        $this->assertTrue(\strpos(\preg_replace('/\r\n|\r|\n/', "\r\n", \stream_get_contents($tmpSaved)), $signableContent) !== false);
        \rewind($tmpSaved);

        $messageWritten = $this->parser->parse($tmpSaved, true);
        $failMessage = 'Failed while parsing saved message for m4007';

        $testString = $messageWritten->getSignedMessageAsString();
        $this->assertEquals($testString, $signableContent);
        $this->assertEquals($this->getSignatureForContent($testString), $signature);

        $props = [
            'From' => [
                'name' => 'Test Sender',
                'email' => 'sender@email.test'
            ],
            'To' => [
                'name' => 'Test Recipient',
                'email' => 'recipient@email.test'
            ],
            'Subject' => 'Test multipart-digest',
            'attachments' => 1,
            'signed' => [
                'protocol' => 'application/pgp-signature',
                'micalg' => 'pgp-sha256',
                'body' => $signature
            ]
        ];

        $this->runEmailTestForMessage($messageWritten, $props, $failMessage);
    }

    public function testReadEmailWithLongHeader()
    {
        $handle = \fopen($this->messageDir . '/m0009.txt', 'r');
        $message = $this->parser->parse($handle, true);

        $this->assertEquals('Normal', $message->getHeaderValue('Importance'));
        $this->assertNotEmpty($message->getHeaderValue('X-Test-Long-Header'));
        $this->assertEquals('A-OK', $message->getHeaderValue('X-Test-Next-Header'));
    }
}
