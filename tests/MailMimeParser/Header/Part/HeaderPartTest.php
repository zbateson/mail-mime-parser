<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;

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
        $charsetConverter = new MbWrapperService();
        $stub = $this->getMockBuilder('\\' . \ZBateson\MailMimeParser\Header\Part\HeaderPart::class)
            ->setConstructorArgs([$charsetConverter])
            ->getMockForAbstractClass();
        $this->abstractHeaderPartStub = $stub;
    }

    public function testIgnoreSpaces() : void
    {
        $this->assertFalse($this->abstractHeaderPartStub->ignoreSpacesBefore());
        $this->assertFalse($this->abstractHeaderPartStub->ignoreSpacesAfter());
    }
}
