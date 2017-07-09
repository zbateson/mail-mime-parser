<?php
namespace ZBateson\MailMimeParser\IntegrationTests;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * Description of ParserHeadersIntegrationTest
 *
 * @group ParserHeadersIntegrationTest
 * @group Base
 * @coversNothing
 * @author Zaahid Bateson
 */
class ParserHeadersIntegrationTest extends PHPUnit_Framework_TestCase
{
    public function testParsingBasicHeaders()
    {
        $parser = new MailMimeParser();
        $message = $parser->parse(fopen(dirname(dirname(__DIR__)) . '/' . TEST_DATA_DIR . '/headers/basic', 'r'));
        $this->assertEquals('Line endings in this file are: CRLF', $message->getHeaderValue('test'));
        $this->assertEquals('More text', $message->getHeaderValue('Second'));
        $this->assertEquals('No Space', $message->getHeaderValue('third'));
        $this->assertEquals('Two lines of text', $message->getHeaderValue('FOURTH'));
        $this->assertEquals('More than two lines of text for a header', $message->getHeaderValue('FiFtH'));
        $this->assertNull($message->getHeaderValue('Body'));
    }
    
    public function testParsingHeadersWithLFOnlyAndNoBody()
    {
        $parser = new MailMimeParser();
        $message = $parser->parse(fopen(dirname(dirname(__DIR__)) . '/' . TEST_DATA_DIR . '/headers/basic-2', 'r'));
        $this->assertEquals('LF Only', $message->getHeaderValue('Line-Endings'));
        $this->assertEquals('text\html', $message->getHeaderValue('Content-Type'));
        $this->assertEquals('With Value', $message->getHeaderValue('Invalid Header'));
        $this->assertEquals('Why not?', $message->getHeaderValue('No-Body'));
    }
    
    public function testParsingHeadersWithLFOnlyAndInvalidHeaders()
    {
        $parser = new MailMimeParser();
        $message = $parser->parse(fopen(dirname(dirname(__DIR__)) . '/' . TEST_DATA_DIR . '/headers/basic-3', 'r'));
        $this->assertEquals('LF Only', $message->getHeaderValue('Line-Endings'));
        $this->assertEquals('', $message->getHeaderValue('Empty-Header'));
        $this->assertEquals(
            'And text on multiple lines that should only be single-spaced when parsed',
            $message->getHeaderValue('Followed-by-a-long-header-line')
        );
    }
    
    public function testParsingHeadersWithEncoding()
    {
        $parser = new MailMimeParser();
        $message = $parser->parse(fopen(dirname(dirname(__DIR__)) . '/' . TEST_DATA_DIR . '/headers/encoded-headers', 'r'));
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
    }
}
