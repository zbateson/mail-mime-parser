<?php
namespace ZBateson\MailMimeParser;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;

/**
 * Description of NonMimePartTest
 *
 * @group NonMimePart
 * @author Zaahid Bateson
 */
class NonMimePartTest extends PHPUnit_Framework_TestCase
{
    public function testNonMimePartContentType()
    {
        $pf = new HeaderPartFactory();
        $cs = new ConsumerService($pf);
        $hf = new HeaderFactory($cs, $pf);
        
        $part = new NonMimePart($hf);
        $this->assertNotNull($part);
        $this->assertEquals('text/plain', $part->getHeaderValue('Content-Type'));
    }
}
