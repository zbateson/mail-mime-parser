<?php
namespace ZBateson\MailMimeParser;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of NonMimePartTest
 *
 * @group NonMimePart
 * @group Base
 * @covers ZBateson\MailMimeParser\NonMimePart
 * @author Zaahid Bateson
 */
class NonMimePartTest extends PHPUnit_Framework_TestCase
{
    public function testNonMimePartContentType()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $cs = new ConsumerService($pf, $mlpf);
        $hf = new HeaderFactory($cs, $pf);
        
        $part = new NonMimePart($hf);
        $this->assertNotNull($part);
        $this->assertEquals('text/plain', $part->getHeaderValue('Content-Type'));
    }
}
