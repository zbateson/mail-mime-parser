<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use Psr\Log\NullLogger;

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

    private function newReceivedPart($name, $childParts)
    {
        return new ReceivedPart($this->logger, $this->mb, $this->hpf, $name, $childParts);
    }

    public function testBasicNameValuePair() : void
    {
        $part = $this->newReceivedPart('Name', $this->getTokenArray('Value'));
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }
}
