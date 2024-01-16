<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\ErrorBag;

/**
 * Description of HeaderPartTest
 *
 * @group HeaderParts
 * @group HeaderPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class HeaderPartTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $abstractHeaderPartStub;

    protected function setUp() : void
    {
        $charsetConverter = new MbWrapper();
        $stub = $this->getMockBuilder('\\' . \ZBateson\MailMimeParser\Header\Part\HeaderPart::class)
            ->setConstructorArgs([$charsetConverter])
            ->getMockForAbstractClass();
        $this->abstractHeaderPartStub = $stub;
    }

    public function testInstance() : void
    {
        $this->assertInstanceOf(ErrorBag::class, $this->abstractHeaderPartStub);
    }

    public function testIgnoreSpaces() : void
    {
        $this->assertFalse($this->abstractHeaderPartStub->ignoreSpacesBefore());
        $this->assertFalse($this->abstractHeaderPartStub->ignoreSpacesAfter());
    }
}
