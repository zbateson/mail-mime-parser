<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use LegacyPHPUnit\TestCase;

/**
 * Description of IdConsumerTest
 *
 * @group Consumers
 * @group IdConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\IdConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class IdConsumerTest extends TestCase
{
    private $idConsumer;

    protected function legacySetUp()
    {
        $charsetConverter = $this->getMockBuilder('ZBateson\MbWrapper\MbWrapper')
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
        $this->idConsumer = new IdConsumer($cs, $pf);
    }

    public function testConsumeId()
    {
        $ret = $this->idConsumer->__invoke('id123@host.name>');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $address = $ret[0];
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\LiteralPart', $address);
        $this->assertEquals('id123@host.name', $address->getValue());
    }

    public function testConsumeSpaces()
    {
        $ret = $this->idConsumer->__invoke('An id without an end');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $this->assertEquals('Anidwithoutanend', $ret[0]->getValue());
    }

    public function testConsumeIdWithComments()
    {
        $ret = $this->idConsumer->__invoke('first (comment) "quoted"');
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);

        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\LiteralPart', $ret[0]);
        $this->assertEquals('firstquoted', $ret[0]->getValue());
    }
}
