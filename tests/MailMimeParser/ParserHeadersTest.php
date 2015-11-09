<?php

use ZBateson\MailMimeParser\Parser;

/**
 * Description of ParserTest
 *
 * @author Zaahid Bateson
 */
class ParserHeadersTest extends \PHPUnit_Framework_TestCase
{
    public function testParsingBasicHeaders()
    {
        $parser = new Parser();
        $message = $parser->parse(fopen(dirname(__DIR__) . '/' . TEST_DATA_DIR . '/headers/basic', 'r'));
        $this->assertEquals('Line endings in this file are: CRLF', $message->getHeaderValue('test'));
        $this->assertEquals('More text', $message->getHeaderValue('Second'));
        $this->assertEquals('No Space', $message->getHeaderValue('third'));
        $this->assertEquals('Two lines of text', $message->getHeaderValue('FOURTH'));
        $this->assertEquals('More than two lines of text for a header', $message->getHeaderValue('FiFtH'));
        $this->assertNull($message->getHeaderValue('Body'));
    }
    
    public function testParsingHeadersWithLFOnlyAndNoBody()
    {
        $parser = new Parser();
        $message = $parser->parse(fopen(dirname(__DIR__) . '/' . TEST_DATA_DIR . '/headers/basic-2', 'r'));
        $this->assertEquals('LF Only', $message->getHeaderValue('Line-Endings'));
        $this->assertEquals('text\html', $message->getHeaderValue('Content-Type'));
        $this->assertEquals('With Value', $message->getHeaderValue('Invalid Header'));
        $this->assertEquals('Why not?', $message->getHeaderValue('No-Body'));
    }
    
    public function testParsingHeadersWithLFOnlyAndInvalidHeaders()
    {
        $parser = new Parser();
        $message = $parser->parse(fopen(dirname(__DIR__) . '/' . TEST_DATA_DIR . '/headers/basic-3', 'r'));
        $this->assertEquals('LF Only', $message->getHeaderValue('Line-Endings'));
        $this->assertEquals('', $message->getHeaderValue('Empty-Header'));
        $this->assertEquals(
            'And text on multiple lines that should only be single-spaced when parsed',
            $message->getHeaderValue('Followed-by-a-long-header-line')
        );
    }
    
    public function testParsingHeadersWithEncoding()
    {
        $parser = new Parser();
        $message = $parser->parse(fopen(dirname(__DIR__) . '/' . TEST_DATA_DIR . '/headers/encoded-headers', 'r'));
        $this->assertEquals('¡Hola, señor!', $message->getHeaderValue('Subject'));
        $this->assertEquals('Müller Müzner <muzner@example.com>', $message->getHeaderValue('To'));
        $this->assertEquals('في إيه يا باشا', $message->getHeaderValue('Other'));
        $this->assertEquals(
            '"Jon Snow" <jsnow@example.com>, Müller Müzner <muzner@example.com>',
            $message->getHeaderValue('From')
        );
        $this->assertEquals('Andreas Müzner <andreas.muzner@example.com>', $message->getHeaderValue('Cc'));
        $this->assertEquals('Andreas Müzner <andreas.muzner@example.com>', $message->getHeaderValue('Bcc'));
    }
}
