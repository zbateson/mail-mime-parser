<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;

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

    public function setUp()
    {
        $this->charsetConverter = $this->getMockBuilder('ZBateson\StreamDecorators\Util\CharsetConverter')
			->disableOriginalConstructor()
			->getMock();
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
