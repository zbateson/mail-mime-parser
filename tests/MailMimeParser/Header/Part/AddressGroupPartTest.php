<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of AddressGroupTest
 *
 * @group HeaderParts
 * @group AddressGroupPart
 * @covers ZBateson\MailMimeParser\Header\Part\AddressGroupPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class AddressGroupPartTest extends PHPUnit_Framework_TestCase
{
    public function testNameGroup()
    {
        $name = 'Roman Senate';
        $members = ['Caesar', 'Cicero', 'Cato'];
        $part = new AddressGroupPart($members, $name);
        $this->assertEquals($name, $part->getName());
        $this->assertEquals($members, $part->getAddresses());
        $this->assertEquals($members[0], $part->getAddress(0));
        $this->assertEquals($members[1], $part->getAddress(1));
        $this->assertEquals($members[2], $part->getAddress(2));
        $this->assertNull($part->getAddress(3));
    }
}
