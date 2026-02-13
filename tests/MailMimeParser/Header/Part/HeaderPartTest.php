<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\ErrorBag;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of HeaderPartTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(HeaderPart::class)]
#[Group('HeaderParts')]
#[Group('HeaderPart')]
class HeaderPartTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $abstractHeaderPartStub;

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $charsetConverter = new MbWrapper();
        $stub = $this->getMockBuilder(HeaderPart::class)
            ->setConstructorArgs([$this->logger, $charsetConverter, 'toost'])
            ->onlyMethods(['getErrorBagChildren'])
            ->getMock();
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
