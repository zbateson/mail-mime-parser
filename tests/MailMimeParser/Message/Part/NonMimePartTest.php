<?php
namespace ZBateson\MailMimeParser\Message;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of NonMimePartTest
 *
 * @group NonMimePart
 * @group Message
 * @covers ZBateson\MailMimeParser\Message\NonMimePart
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
        $pw = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Writer\MimePartWriter')
            ->disableOriginalConstructor()
            ->getMock();
        
        $part = new NonMimePart($hf, $pw);
        $this->assertNotNull($part);
        $this->assertEquals('text/plain', $part->getHeaderValue('Content-Type'));
    }
}
