<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;

/**
 * Description of AddressGroupTest
 *
 * @group HeaderParts
 * @group AddressGroupPart
 * @covers ZBateson\MailMimeParser\Header\Part\AddressGroupPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class AddressGroupPartTest extends TestCase
{
    public function testNameGroup()
    {
        $name = 'Roman Senate';
        $members = ['Caesar', 'Cicero', 'Cato'];
        $csConverter = $this->getMockBuilder('ZBateson\StreamDecorators\Util\CharsetConverter')
			->disableOriginalConstructor()
			->getMock();
        $part = new AddressGroupPart($csConverter, $members, $name);
        $this->assertEquals($name, $part->getName());
        $this->assertEquals($members, $part->getAddresses());
        $this->assertEquals($members[0], $part->getAddress(0));
        $this->assertEquals($members[1], $part->getAddress(1));
        $this->assertEquals($members[2], $part->getAddress(2));
        $this->assertNull($part->getAddress(3));
    }
}
