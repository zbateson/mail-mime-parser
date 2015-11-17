<?php

use ZBateson\MailMimeParser\Header\Part\AddressGroupPart;

/**
 * Description of AddressGroupTest
 *
 * @group HeaderParts
 * @group AddressGroupPart
 * @author Zaahid Bateson
 */
class AddressGroupTest extends PHPUnit_Framework_TestCase
{
    public function testNameGroup()
    {
        $name = 'Roman Senate';
        $members = ['Caesar', 'Cicero', 'Cato'];
        $part = new AddressGroupPart($members, $name);
        $this->assertEquals($name, $part->getName());
        $this->assertEquals($members, $part->getAddresses());
    }
}
