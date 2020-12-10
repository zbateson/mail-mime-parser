<?php
namespace ZBateson\MailMimeParser\Header\Part;

use LegacyPHPUnit\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of ReceivedDomainTest
 *
 * @group HeaderParts
 * @group ReceivedDomainPart
 * @covers ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class ReceivedDomainPartTest extends TestCase
{
    private $charsetConverter;

    protected function legacySetUp()
    {
        $this->charsetConverter = new MbWrapper();
    }

    public function testBasicNameValueAndDomainParts()
    {
        $part = new ReceivedDomainPart($this->charsetConverter, 'Name', 'Value', 'ehlo', 'hostname', 'address');
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
        $this->assertEquals('ehlo', $part->getEhloName());
        $this->assertEquals('hostname', $part->getHostname());
        $this->assertEquals('address', $part->getAddress());
    }
}
