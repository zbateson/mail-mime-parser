<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of ReceivedTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(ReceivedPart::class)]
#[CoversClass(HeaderPart::class)]
#[Group('HeaderParts')]
#[Group('ReceivedPart')]
class ReceivedPartTest extends TestCase
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
            ->onlyMethods([])
            ->getMock()];
    }

    private function newReceivedPart($name, $childParts)
    {
        return new ReceivedPart($this->logger, $this->mb, $name, $childParts);
    }

    public function testBasicNameValuePair() : void
    {
        $part = $this->newReceivedPart('Name', $this->getTokenArray('Value'));
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }
}
