<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

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
    private $abstractHeaderPartStub;

    protected function setUp() : void
    {
        $charsetConverter = new MbWrapper();
        $stub = $this->getMockBuilder('\ZBateson\MailMimeParser\Header\Part\HeaderPart')
            ->setConstructorArgs([$charsetConverter])
            ->getMockForAbstractClass();
        $this->abstractHeaderPartStub = $stub;
    }

    public function testIgnoreSpaces()
    {
        $this->assertFalse($this->abstractHeaderPartStub->ignoreSpacesBefore());
        $this->assertFalse($this->abstractHeaderPartStub->ignoreSpacesAfter());
    }
}
