<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of LiteralTest
 *
 * @group HeaderParts
 * @group ContainerPart
 * @covers ZBateson\MailMimeParser\Header\Part\ContainerPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class ContainerPartTest extends TestCase
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

    private function newContainerPart($childParts)
    {
        return new ContainerPart($this->logger, $this->mb, $this->hpf, $childParts);
    }

    public function testInstance() : void
    {
        $part = $this->newContainerPart($this->getTokenArray('"'));
        $this->assertNotNull($part);
        $this->assertEquals('"', $part->getValue());

        $part = $this->newContainerPart($this->getTokenArray('=?US-ASCII?Q?Kilgore_Trout?='));
        $this->assertEquals('=?US-ASCII?Q?Kilgore_Trout?=', $part->getValue());
    }
}
