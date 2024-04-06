<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;

use Psr\Log\LogLevel;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of AddressGroupPartTest
 *
 * @group HeaderParts
 * @group AddressGroupPart
 * @covers ZBateson\MailMimeParser\Header\Part\AddressGroupPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class AddressGroupPartTest extends TestCase
{
    private $mb;
    private $hpf;

    protected function setUp() : void
    {
        $this->mb = new MbWrapper();
        $this->hpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->mb])
            ->setMethods(['__toString'])
            ->getMock();
    }

    public function testNameGroup() : void
    {
        $name = $this->getMockForAbstractClass(HeaderPart::class, [$this->mb, 'Roman Senate']);
        $members = [
            $this->getMockBuilder(AddressPart::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(AddressPart::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(AddressPart::class)->disableOriginalConstructor()->getMock()
        ];

        $part = new AddressGroupPart($this->mb, $this->hpf, [$name], $members);
        $this->assertEquals('Roman Senate', $part->getName());
        $this->assertEquals($members, $part->getAddresses());
        $this->assertEquals($members[0], $part->getAddress(0));
        $this->assertEquals($members[1], $part->getAddress(1));
        $this->assertEquals($members[2], $part->getAddress(2));
        $this->assertNull($part->getAddress(3));
    }

    public function testValidation() : void
    {
        $part = new AddressGroupPart($this->mb, $this->hpf, [], []);
        $errs = $part->getErrors(true, LogLevel::NOTICE);
        $this->assertCount(2, $errs);
        $this->assertEquals('Address group doesn\'t have a name', $errs[0]->getMessage());
        $this->assertEquals(LogLevel::ERROR, $errs[0]->getPsrLevel());
        $this->assertEquals('Address group doesn\'t have any email addresses defined in it', $errs[1]->getMessage());
        $this->assertEquals(LogLevel::NOTICE, $errs[1]->getPsrLevel());
    }
}
