<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of ReceivedTest
 *
 * @group HeaderParts
 * @group ReceivedPart
 * @covers ZBateson\MailMimeParser\Header\Part\ReceivedPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class ReceivedPartTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mb;
    private $hpf;

    protected function setUp() : void
    {
        $this->mb = new MbWrapper();
        $this->hpf = $this->getMockBuilder(HeaderPartFactory::class)
            ->setConstructorArgs([$this->mb])
            ->setMethods()
            ->getMock();
    }

    private function getTokenArray(string $name) : array
    {
        return [$this->getMockBuilder(MimeToken::class)
            ->setConstructorArgs([$this->mb, $name])
            ->setMethods()
            ->getMock()];
    }

    public function testBasicNameValuePair() : void
    {
        $part = new ReceivedPart($this->mb, $this->hpf, 'Name', $this->getTokenArray('Value'));
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }
}
