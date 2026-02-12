<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;

use Psr\Log\LogLevel;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of AddressGroupPartTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(AddressGroupPart::class)]
#[CoversClass(HeaderPart::class)]
#[Group('HeaderParts')]
#[Group('AddressGroupPart')]
class AddressGroupPartTest extends TestCase
{
    private $mb;

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $this->mb = new MbWrapper();
    }

    private function newAddressGroupPart($nameParts, $addressAndGroupParts)
    {
        return new AddressGroupPart(
            $this->logger,
            $this->mb,
            $nameParts,
            $addressAndGroupParts
        );
    }

    public function testNameGroup() : void
    {
        $name = $this->getMockForAbstractClass(HeaderPart::class, [$this->logger, $this->mb, 'Roman Senate']);
        $members = [
            $this->getMockBuilder(AddressPart::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(AddressPart::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(AddressPart::class)->disableOriginalConstructor()->getMock()
        ];

        $part = $this->newAddressGroupPart([$name], $members);
        $this->assertEquals('Roman Senate', $part->getName());
        $this->assertEquals('Roman Senate', $part->getValue());
        $this->assertEquals($members, $part->getAddresses());
        $this->assertEquals($members[0], $part->getAddress(0));
        $this->assertEquals($members[1], $part->getAddress(1));
        $this->assertEquals($members[2], $part->getAddress(2));
        $this->assertNull($part->getAddress(3));
    }

    public function testValidation() : void
    {
        $part = $this->newAddressGroupPart([], []);
        $errs = $part->getErrors(true, LogLevel::NOTICE);
        $this->assertCount(2, $errs);
        $this->assertNotEmpty($errs[0]->getMessage());
        $this->assertEquals(LogLevel::ERROR, $errs[0]->getPsrLevel());
        $this->assertNotEmpty($errs[1]->getMessage());
        $this->assertEquals(LogLevel::NOTICE, $errs[1]->getPsrLevel());
    }
}
