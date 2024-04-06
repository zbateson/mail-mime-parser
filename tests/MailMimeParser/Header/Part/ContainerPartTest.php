<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
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

    public function testInstance() : void
    {
        $part = new ContainerPart($this->mb, $this->hpf, $this->getTokenArray('"'));
        $this->assertNotNull($part);
        $this->assertEquals('"', $part->getValue());

        $part = new ContainerPart($this->mb, $this->hpf, $this->getTokenArray('=?US-ASCII?Q?Kilgore_Trout?='));
        $this->assertEquals('=?US-ASCII?Q?Kilgore_Trout?=', $part->getValue());
    }
}
