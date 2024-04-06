<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\ErrorBag;
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
    // @phpstan-ignore-next-line
    private $abstractHeaderPartStub;

    protected function setUp() : void
    {
        $charsetConverter = new MbWrapper();
        $stub = $this->getMockBuilder(HeaderPart::class)
            ->setConstructorArgs([$charsetConverter, 'toost'])
            ->getMockForAbstractClass();
        $this->abstractHeaderPartStub = $stub;
    }

    public function testInstance() : void
    {
        $this->assertInstanceOf(ErrorBag::class, $this->abstractHeaderPartStub);
    }

    public function testValue() : void
    {
        $this->assertEquals('toost', $this->abstractHeaderPartStub->getValue());
        $this->assertEquals('toost', $this->abstractHeaderPartStub->__toString());
    }
}
