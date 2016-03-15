<?php
namespace ZBateson\MailMimeParser;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of UUEncodedPartTest
 *
 * @group UUEncodedPart
 * @group Base
 * @covers ZBateson\MailMimeParser\UUEncodedPart
 * @author Zaahid Bateson
 */
class UUEncodedPartTest extends PHPUnit_Framework_TestCase
{
    public function testUUEncodedPartHeadersAndMembers()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $cs = new ConsumerService($pf, $mlpf);
        $hf = new HeaderFactory($cs, $pf);
        
        $part = new UUEncodedPart($hf, 0754, 'test-file.ext');
        $this->assertNotNull($part);
        
        $this->assertEquals('application/octet-stream', $part->getHeaderValue('Content-Type'));
        $this->assertEquals('test-file.ext', $part->getHeaderParameter('Content-Type', 'name'));
        $this->assertEquals('x-uuencode', $part->getHeaderValue('Content-Transfer-Encoding'));
        $this->assertEquals('attachment', $part->getHeaderValue('Content-Disposition'));
        $this->assertEquals('test-file.ext', $part->getHeaderParameter('Content-Disposition', 'filename'));
        $this->assertEquals('test-file.ext', $part->getFilename());
        $this->assertEquals(0754, $part->getUnixFileMode());
    }
}
