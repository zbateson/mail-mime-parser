<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
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
        $this->logger = new NullLogger();
        $this->mb = new MbWrapper();
        $this->hpf = $this->getMockBuilder(HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $this->mb])
            ->setMethods()
            ->getMock();
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
        $part = new ReceivedDomainPart($this->logger, $this->mb, $this->hpf, 'Name', $this->getTokenArray('Value'));
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
        $this->assertEquals('Value', $part->getEhloName());
        $this->assertNull($part->getHostname());
        $this->assertNull($part->getAddress());
    }
}
