<?php

namespace ZBateson\MailMimeParser\IntegrationTests;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * Description of ParserHeadersIntegrationTest
 *
 * @group ParserHeadersIntegrationTest
 * @group Functional
 * @coversNothing
 * @author Zaahid Bateson
 */
class ParserHeadersIntegrationTest extends TestCase
{
    public function testParsingBasicHeaders() : void
    {
        $parser = new MailMimeParser();
        $message = $parser->parse(\fopen(\dirname(__DIR__, 2) . '/' . TEST_DATA_DIR . '/headers/basic', 'r'), true);
        $this->assertEquals('Line endings in this file are: CRLF', $message->getHeaderValue('test'));
        $this->assertEquals('More text', $message->getHeaderValue('Second'));
        $this->assertEquals('No Space', $message->getHeaderValue('third'));
        $this->assertEquals('Two lines of text', $message->getHeaderValue('FOURTH'));
        $this->assertEquals('More than two lines of text for a header', $message->getHeaderValue('FiFtH'));
        $this->assertNull($message->getHeaderValue('Body'));
    }

    public function testParsingHeadersWithLFOnlyAndNoBody() : void
    {
        $parser = new MailMimeParser();
        $message = $parser->parse(\fopen(\dirname(__DIR__, 2) . '/' . TEST_DATA_DIR . '/headers/basic-2', 'r'), true);
        $this->assertEquals('LF Only', $message->getHeaderValue('Line-Endings'));
        $this->assertEquals('text\html', $message->getHeaderValue('Content-Type'));
        $this->assertEquals('With Value', $message->getHeaderValue('Invalid Header'));
        $this->assertEquals('Why not?', $message->getHeaderValue('No-Body'));
    }

    public function testParsingHeadersWithLFOnlyAndInvalidHeaders() : void
    {
        $parser = new MailMimeParser();
        $message = $parser->parse(\fopen(\dirname(__DIR__, 2) . '/' . TEST_DATA_DIR . '/headers/basic-3', 'r'), true);
        $this->assertEquals('LF Only', $message->getHeaderValue('Line-Endings'));
        $this->assertEquals('', $message->getHeaderValue('Empty-Header'));
        $this->assertEquals(
            'And text on multiple lines that should only be single-spaced when parsed',
            $message->getHeaderValue('Followed-by-a-long-header-line')
        );
    }

    public function testParsingHeadersWithEncoding() : void
    {
        $parser = new MailMimeParser();
        $message = $parser->parse(\fopen(\dirname(__DIR__, 2) . '/' . TEST_DATA_DIR . '/headers/encoded-headers', 'r'), true);
        $this->assertEquals('¡Hola, señor!', $message->getHeaderValue('Subject'));
        $this->assertEquals('muzner@example.com', $message->getHeaderValue('To'));
        $this->assertEquals('Müller Müzner', $message->getHeader('To')->getPersonName());
        $this->assertEquals('في إيه يا باشا', $message->getHeaderValue('Other'));

        $parts = $message->getHeader('From')->getParts();
        $this->assertEquals('jsnow@example.com', $parts[0]->getEmail());
        $this->assertEquals('Jon Snow', $parts[0]->getName());
        $this->assertEquals('muzner@example.com', $parts[1]->getEmail());
        $this->assertEquals('Müller Müzner', $parts[1]->getName());

        $this->assertEquals('Andreas Müzner', $message->getHeader('Cc')->getPersonName());
        $this->assertEquals('Andreas Müzner', $message->getHeader('Bcc')->getPersonName());

        $this->assertEquals('Технические работы (ERP Галактика и Отчеты ТД)', $message->getHeaderValue('Test'));

        $this->assertEquals('FAMILY eCarsharing GAUTING STA-CL51E - Buchung geändert Text', $message->getHeaderValue('Subject-X'));
    }

    public function testParsingHeadersWithInvalidCharset() : void
    {
        $parser = new MailMimeParser();
        $message = $parser->parse(\fopen(\dirname(__DIR__, 2) . '/' . TEST_DATA_DIR . '/headers/invalid-charset', 'r'), true);
        $header = $message->getHeader('subject');
        $this->assertEquals('TEST ¡Hola, señor!', $header->getValue());
        $errs = $message->getAllErrors();
        $this->assertCount(1, $errs);
        $err = $errs[0];
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Header\Part\MimeToken::class, $err->getObject());
        $this->assertInstanceOf(\ZBateson\MbWrapper\UnsupportedCharsetException::class, $err->getException());
    }
}
