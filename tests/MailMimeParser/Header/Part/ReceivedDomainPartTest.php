<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
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
    // @phpstan-ignore-next-line
    private $charsetConverter;

    protected function setUp() : void
    {
        $this->charsetConverter = new MbWrapper();
    }

    public function testBasicNameValueAndDomainParts() : void
    {
        $part = new ReceivedDomainPart($this->charsetConverter, 'Name', 'Value', 'ehlo', 'hostname', 'address');
        $this->assertEquals('Name', $part->getName());
        $this->assertEquals('Value', $part->getValue());
        $this->assertEquals('ehlo', $part->getEhloName());
        $this->assertEquals('hostname', $part->getHostname());
        $this->assertEquals('address', $part->getAddress());
    }
}
