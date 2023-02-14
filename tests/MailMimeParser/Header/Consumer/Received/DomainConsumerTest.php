<?php

namespace ZBateson\MailMimeParser\Header\Consumer\Received;

use PHPUnit\Framework\TestCase;

/**
 * Description of DomainConsumerTest
 *
 * @group Consumers
 * @group DomainConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumer
 * @author Zaahid Bateson
 */
class DomainConsumerTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $domainConsumer;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mlpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $cs = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\ConsumerService::class)
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
        $this->domainConsumer = new DomainConsumer($cs, $pf, 'from');
    }

    public function testConsumeParts() : void
    {
        $aTests = [
            ['hello (blah.blooh [1.2.3.4])', ['ehloName' => 'hello', 'hostname' => 'blah.blooh', 'address' => '1.2.3.4'], []],
            ['hello (helo=blah.blooh)', ['ehloName' => 'hello', 'hostname' => 'blah.blooh'], []],
            ['hello ([1.2.3.4])', ['ehloName' => 'hello', 'address' => '1.2.3.4'], []],
            ['hello ([1.2.3.4:333])', ['ehloName' => 'hello', 'address' => '1.2.3.4:333'], []],
            ['hello ([::1])', ['ehloName' => 'hello', 'address' => '::1'], []],
            ['hello ([ipv6::1])', ['ehloName' => 'hello', 'address' => '::1'], []],
            ['hello ([2001:0db8:85a3:0000:0000:8a2e:0370:7334])', ['ehloName' => 'hello', 'address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'], []],
            ['hello', ['ehloName' => 'hello'], []],
            ['hello (blah.blooh [1.2.3.4]) (TEST)', ['ehloName' => 'hello', 'hostname' => 'blah.blooh', 'address' => '1.2.3.4'], []],
            ['(blah-blooh)', ['hostname' => 'blah-blooh'], []],
            ['hello ([1.2.3.4] blah.blooh)', ['ehloName' => 'hello', 'hostname' => 'blah.blooh', 'address' => '1.2.3.4'], []],
            ['hello ([1.2.3.4] helo=blah.blooh)', ['ehloName' => 'hello', 'hostname' => 'blah.blooh', 'address' => '1.2.3.4'], []],
            ['hello (helo=blah.blooh [1.2.3.4])', ['ehloName' => 'hello', 'hostname' => 'blah.blooh', 'address' => '1.2.3.4'], []],
            ['(negatron)', ['hostname' => 'negatron'], []],
            ['(.negatron)', [], ['.negatron']],
        ];

        foreach ($aTests as $test) {
            $ret = $this->domainConsumer->__invoke($test[0]);
            $this->assertNotEmpty($ret, $test[0]);
            $this->assertCount(1 + \count($test[2]), $ret, $test[0]);

            $pt = $test[1];
            $domPart = $ret[0];
            $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart::class, $domPart);
            $this->assertEquals('from', $domPart->getName());
            if (isset($pt['ehloName'])) {
                $this->assertEquals($pt['ehloName'], $domPart->getEhloName(), $test[0]);
            } else {
                $this->assertNull($domPart->getEhloName(), $test[0]);
            }
            if (isset($pt['hostname'])) {
                $this->assertEquals($pt['hostname'], $domPart->getHostname(), $test[0]);
            } else {
                $this->assertNull($domPart->getHostname(), $test[0]);
            }
            if (isset($pt['address'])) {
                $this->assertEquals($pt['address'], $domPart->getAddress(), $test[0]);
            } else {
                $this->assertNull($domPart->getAddress(), $test[0]);
            }

            foreach ($test[2] as $comment) {
                $this->assertNotNull($ret[1], $test[0]);
                $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\CommentPart::class, $ret[1], $test[0]);
                $this->assertEquals($comment, $ret[1]->getComment(), $test[0]);
            }
        }
    }
}
