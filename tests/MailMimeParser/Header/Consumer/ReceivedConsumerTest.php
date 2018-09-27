<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use DateTime;

/**
 * Description of ReceivedConsumerTest
 *
 * @group Consumers
 * @group ReceivedConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\ReceivedConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\Received\ReceivedDateConsumer
 * @author Zaahid Bateson
 */
class ReceivedConsumerTest extends TestCase
{
    private $receivedConsumer;

    protected function setUp()
    {
        $charsetConverter = $this->getMockBuilder('ZBateson\StreamDecorators\Util\CharsetConverter')
			->setMethods(['__toString'])
			->getMock();
        $pf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\HeaderPartFactory')
			->setConstructorArgs([$charsetConverter])
			->setMethods(['__toString'])
			->getMock();
        $mlpf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory')
			->setConstructorArgs([$charsetConverter])
			->setMethods(['__toString'])
			->getMock();
        $cs = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Consumer\ConsumerService')
			->setConstructorArgs([$pf, $mlpf])
			->setMethods(['__toString'])
			->getMock();
        $this->receivedConsumer = new ReceivedConsumer($cs, $pf);
    }

    public function testInvalidLine()
    {
        $value = "Je \t suis\nici";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertEmpty($ret);
    }

    public function testWithFrom()
    {
        $value = "from [1.2.3.4]";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart', $ret[0]);
        $this->assertEquals('[1.2.3.4]', $ret[0]->getEhloName());
    }

    public function testWithBy()
    {
        $value = "by [1.2.3.4]";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart', $ret[0]);
        $this->assertEquals('[1.2.3.4]', $ret[0]->getEhloName());
    }

    public function testWithFromAndBy()
    {
        $value = "FrOM [1.2.3.4] By (host.name)";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart', $ret[0]);
        $this->assertEquals('from', $ret[0]->getName());
        $this->assertEquals('[1.2.3.4]', $ret[0]->getEhloName());
        $this->assertEquals('by', $ret[1]->getName());
        $this->assertEquals('host.name', $ret[1]->getHostname());
        $this->assertNull($ret[1]->getEhloName());
    }

    public function testWithWith()
    {
        $value = "WITH ESMTP (TLS1.2)";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ReceivedPart', $ret[0]);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\CommentPart', $ret[1]);
        $this->assertEquals('with', $ret[0]->getName());
        $this->assertEquals('ESMTP', $ret[0]->getValue());
        $this->assertEquals('TLS1.2', $ret[1]->getComment());
    }

    public function testWithId()
    {
        $value = "id (blah) 123";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ReceivedPart', $ret[0]);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\CommentPart', $ret[1]);
        $this->assertEquals('id', $ret[0]->getName());
        $this->assertEquals('123', $ret[0]->getValue());
        $this->assertEquals('blah', $ret[1]->getComment());
    }

    public function testWithVia()
    {
        $value = "via someplace";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ReceivedPart', $ret[0]);
        $this->assertEquals('via', $ret[0]->getName());
        $this->assertEquals('someplace', $ret[0]->getValue());
    }

    public function testDate()
    {
        $value = "; Wed, 17 May 2000 19:08:29 -0400";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\DatePart', $ret[0]);
        $dt = $ret[0]->getDateTime();
        $this->assertEquals('2000-05-17T19:08:29-04:00', $dt->format(DateTime::RFC3339));
    }

    public function testFromAndDate()
    {
        $value = "from localhost; Wed, 17 May 2000 19:08:29 -0400";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);

        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart', $ret[0]);
        $this->assertEquals('localhost', $ret[0]->getEhloName());

        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\DatePart', $ret[1]);
        $dt = $ret[1]->getDateTime();
        $this->assertEquals('2000-05-17T19:08:29-04:00', $dt->format(DateTime::RFC3339));
    }

    public function testExampleFullLines()
    {
        $value = "FROM LeComputer (blah.host) by MyComputer ([1.2.2.2]) WITH\n"
            . "ESMTP (TLS BLAH) ID 123; Wed, 17 May 2000 19:08:29 -0400";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);

        $this->assertCount(6, $ret);
        
        $this->assertEquals('from', $ret[0]->getName());
        $this->assertEquals('LeComputer', $ret[0]->getEhloName());
        $this->assertEquals('blah.host', $ret[0]->getHostname());

        $this->assertEquals('by', $ret[1]->getName());
        $this->assertEquals('MyComputer', $ret[1]->getEhloName());
        $this->assertEquals('1.2.2.2', $ret[1]->getAddress());

        $this->assertEquals('with', $ret[2]->getName());
        $this->assertEquals('ESMTP', $ret[2]->getValue());

        $this->assertEquals('TLS BLAH', $ret[3]->getComment());

        $this->assertEquals('id', $ret[4]->getName());
        $this->assertEquals('123', $ret[4]->getValue());

        $dt = $ret[5]->getDateTime();
        $this->assertEquals('2000-05-17T19:08:29-04:00', $dt->format(DateTime::RFC3339));
    }
}
