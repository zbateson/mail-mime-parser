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
    private $domainConsumer;

    protected function setUp(): void
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
        $this->domainConsumer = new DomainConsumer($cs, $pf, 'from');
    }

    public function testConsumeParts()
    {
        $aTests = [
            [ 'hello (blah.blooh [1.2.3.4])', [ 'ehloName' => 'hello', 'hostname' => 'blah.blooh', 'address' => '1.2.3.4' ], [] ],
            [ 'hello (blah.blooh)', [ 'ehloName' => 'hello', 'hostname' => 'blah.blooh' ], [] ],
            [ 'hello ([1.2.3.4])', [ 'ehloName' => 'hello', 'address' => '1.2.3.4' ], [] ],
            [ 'hello ([1.2.3.4:333])', [ 'ehloName' => 'hello', 'address' => '1.2.3.4:333' ], [] ],
            [ 'hello ([::1])', [ 'ehloName' => 'hello', 'address' => '::1' ], [] ],
            [ 'hello ([ipv6::1])', [ 'ehloName' => 'hello', 'address' => '::1' ], [] ],
            [ 'hello ([2001:0db8:85a3:0000:0000:8a2e:0370:7334])', [ 'ehloName' => 'hello', 'address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334' ], [] ],
            [ 'hello', [ 'ehloName' => 'hello' ], [] ],
            [ 'hello (blah.blooh [1.2.3.4]) (TEST)', [ 'ehloName' => 'hello', 'hostname' => 'blah.blooh', 'address' => '1.2.3.4' ], [] ],
            [ 'hello (TEST)', [ 'ehloName' => 'hello' ], [ 'TEST' ] ],
            [ '(blah.blooh)', [ 'hostname' => 'blah.blooh' ], [] ],
            [ '(negatron)', [ ], [ 'negatron' ] ],
        ];

        foreach ($aTests as $test) {
            $ret = $this->domainConsumer->__invoke($test[0]);
            $this->assertNotEmpty($ret, $test[0]);
            $this->assertCount(1 + count($test[2]), $ret, $test[0]);

            $pt = $test[1];
            $domPart = $ret[0];
            $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart', $domPart);
            $this->assertEquals('from', $domPart->getName());
            if (isset($pt['ehloName'])) {
                $this->assertEquals($pt['ehloName'], $domPart->getEhloName());
            } else {
                $this->assertNull($domPart->getEhloName());
            }
            if (isset($pt['hostname'])) {
                $this->assertEquals($pt['hostname'], $domPart->getHostname());
            } else {
                $this->assertNull($domPart->getHostname());
            }
            if (isset($pt['address'])) {
                $this->assertEquals($pt['address'], $domPart->getAddress());
            } else {
                $this->assertNull($domPart->getAddress());
            }

            foreach ($test[2] as $comment) {
                $this->assertNotNull($ret[1]);
                $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\CommentPart', $ret[1], $test[0]);
                $this->assertEquals($comment, $ret[1]->getComment(), $test[0]);
            }
        }
    }
}
