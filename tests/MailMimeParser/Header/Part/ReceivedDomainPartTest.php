<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of ReceivedDomainTest
 *
 * @group HeaderParts
 * @group ReceivedDomainPart
 * @covers ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class ReceivedDomainPartTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mb;
    private $hpf;
    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $this->mb = new MbWrapper();
    }

    private function getTokenArray(string $name) : array
    {
        return [$this->getMockBuilder(MimeToken::class)
            ->setConstructorArgs([$this->logger, $this->mb, $name])
            ->setMethods()
            ->getMock()];
    }

    public function testBasicNameValueAndDomainParts() : void
    {
        $part = new ReceivedDomainPart($this->logger, $this->mb, 'Name', $this->getTokenArray('Value'));
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
        $this->assertEquals('Value', $part->getEhloName());
        $this->assertNull($part->getHostname());
        $this->assertNull($part->getAddress());
    }
}
