<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use DateTime;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\ReceivedDateConsumerService;

/**
 * Description of ReceivedConsumerServiceTest
 *
 * @group Consumers
 * @group ReceivedConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\ReceivedConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\Received\ReceivedDateConsumerService
 * @author Zaahid Bateson
 */
class ReceivedConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $receivedConsumer;
    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods()
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->setMethods()
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $qscs])
            ->setMethods()
            ->getMock();

        $fdcs = $this->getMockBuilder(DomainConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, 'from'])
            ->setMethods()
            ->getMock();
        $bdcs = $this->getMockBuilder(DomainConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, 'by'])
            ->setMethods()
            ->getMock();
        $vgcs = $this->getMockBuilder(GenericReceivedConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, 'via'])
            ->setMethods()
            ->getMock();
        $wgcs = $this->getMockBuilder(GenericReceivedConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, 'with'])
            ->setMethods()
            ->getMock();
        $igcs = $this->getMockBuilder(GenericReceivedConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, 'id'])
            ->setMethods()
            ->getMock();
        $fgcs = $this->getMockBuilder(GenericReceivedConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, 'for'])
            ->setMethods()
            ->getMock();
        $rdcs = $this->getMockBuilder(ReceivedDateConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, $qscs])
            ->setMethods()
            ->getMock();

        $this->receivedConsumer = new ReceivedConsumerService(
            $this->logger, $pf, $fdcs, $bdcs, $vgcs, $wgcs, $igcs, $fgcs, $rdcs, $ccs
        );
    }

    public function testInvalidLine() : void
    {
        $value = "Je \t suis\nici";

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertEmpty($ret);
    }

    public function testWithFrom() : void
    {
        $value = 'from [1.2.3.4]';

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart::class, $ret[0]);
        $this->assertEquals('[1.2.3.4]', $ret[0]->getEhloName());
    }

    public function testWithBy() : void
    {
        $value = 'by [1.2.3.4]';

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart::class, $ret[0]);
        $this->assertEquals('[1.2.3.4]', $ret[0]->getEhloName());
    }

    public function testWithFromAndBy() : void
    {
        $value = 'FrOM [1.2.3.4] By (host.name)';

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart::class, $ret[0]);
        $this->assertEquals('from', $ret[0]->getName());
        $this->assertEquals('[1.2.3.4]', $ret[0]->getEhloName());
        $this->assertEquals('by', $ret[1]->getName());
        $this->assertEquals('host.name', $ret[1]->getHostname());
        $this->assertNull($ret[1]->getEhloName());
    }

    public function testWithWith() : void
    {
        $value = 'WITH ESMTP (TLS1.2)';

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ReceivedPart::class, $ret[0]);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\CommentPart::class, $ret[1]);
        $this->assertEquals('with', $ret[0]->getName());
        $this->assertEquals('ESMTP', $ret[0]->getValue());
        $this->assertEquals('TLS1.2', $ret[1]->getComment());
    }

    public function testWithId() : void
    {
        $value = 'id (blah) 123';

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ReceivedPart::class, $ret[0]);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\CommentPart::class, $ret[1]);
        $this->assertEquals('id', $ret[0]->getName());
        $this->assertEquals('123', $ret[0]->getValue());
        $this->assertEquals('blah', $ret[1]->getComment());
    }

    public function testWithVia() : void
    {
        $value = 'via someplace';

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ReceivedPart::class, $ret[0]);
        $this->assertEquals('via', $ret[0]->getName());
        $this->assertEquals('someplace', $ret[0]->getValue());
    }

    public function testDate() : void
    {
        $value = '; Wed, 17 May 2000 19:08:29 -0400';

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\DatePart::class, $ret[0]);
        $dt = $ret[0]->getDateTime();
        $this->assertEquals('2000-05-17T19:08:29-04:00', $dt->format(DateTime::RFC3339));
    }

    public function testFromAndDate() : void
    {
        $value = 'from localhost; Wed, 17 May 2000 19:08:29 -0400';

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ReceivedDomainPart::class, $ret[0]);
        $this->assertEquals('localhost', $ret[0]->getEhloName());

        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\DatePart::class, $ret[1]);
        $dt = $ret[1]->getDateTime();
        $this->assertEquals('2000-05-17T19:08:29-04:00', $dt->format(DateTime::RFC3339));
    }

    public function testExampleFullLines() : void
    {
        $value = "FROM LeComputer (blah.host) by MyComputer ([1.2.2.2]) WITH\n"
            . 'ESMTP (TLS BLAH) ID 123; Wed, 17 May 2000 19:08:29 -0400';

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

    public function testExampleFullLine2() : void
    {
        $value = "from xcv.gurbuzsrc.com ([69.69.69.69])\nby mail.yetiforce.com with esmtp (Exim 4.94)\n"
            . "(envelope-from <xxcv@gurbuzsrc.com>)\nid 1kfCyx-0002Zp-BY\n"
            . 'for vbc@yetiforce.com; Wed, 18 Nov 2020 03:14:03 +0100';

        $ret = $this->receivedConsumer->__invoke($value);
        $this->assertNotEmpty($ret);

        $this->assertCount(8, $ret);

        $this->assertEquals('from', $ret[0]->getName());
        $this->assertEquals('xcv.gurbuzsrc.com', $ret[0]->getEhloName());
        $this->assertEquals('69.69.69.69', $ret[0]->getAddress());      // nice

        $this->assertEquals('by', $ret[1]->getName());
        $this->assertEquals('mail.yetiforce.com', $ret[1]->getEhloName());

        $this->assertEquals('with', $ret[2]->getName());
        $this->assertEquals('esmtp', $ret[2]->getValue());

        $this->assertEquals('id', $ret[5]->getName());
        $this->assertEquals('1kfCyx-0002Zp-BY', $ret[5]->getValue());

        $this->assertEquals('for', $ret[6]->getName());
        $this->assertEquals('vbc@yetiforce.com', $ret[6]->getValue());

        $dt = $ret[7]->getDateTime();
        $this->assertEquals('2020-11-18T03:14:03+01:00', $dt->format(DateTime::RFC3339));
    }
}
