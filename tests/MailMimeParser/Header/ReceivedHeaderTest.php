<?php

namespace ZBateson\MailMimeParser\Header;

use DateTime;
use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\ReceivedDateConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ReceivedConsumerService;

/**
 * Description of ReceivedHeaderTest
 *
 * @group Headers
 * @group ReceivedHeader
 * @covers ZBateson\MailMimeParser\Header\ReceivedHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class ReceivedHeaderTest extends TestCase
{
    // @phpstan-ignore-next-line
    protected $consumerService;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$pf])
            ->setMethods(['__toString'])
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$mpf, $qscs])
            ->setMethods(['__toString'])
            ->getMock();

        $fdcs = $this->getMockBuilder(DomainConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, 'from'])
            ->setMethods(['__toString'])
            ->getMock();
        $bdcs = $this->getMockBuilder(DomainConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, 'by'])
            ->setMethods(['__toString'])
            ->getMock();
        $vgcs = $this->getMockBuilder(GenericReceivedConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, 'via'])
            ->setMethods(['__toString'])
            ->getMock();
        $wgcs = $this->getMockBuilder(GenericReceivedConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, 'with'])
            ->setMethods(['__toString'])
            ->getMock();
        $igcs = $this->getMockBuilder(GenericReceivedConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, 'id'])
            ->setMethods(['__toString'])
            ->getMock();
        $fgcs = $this->getMockBuilder(GenericReceivedConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, 'for'])
            ->setMethods(['__toString'])
            ->getMock();
        $rdcs = $this->getMockBuilder(ReceivedDateConsumerService::class)
            ->setConstructorArgs([$pf, $ccs, $qscs])
            ->setMethods(['__toString'])
            ->getMock();
        $this->consumerService = $this->getMockBuilder(ReceivedConsumerService::class)
            ->setConstructorArgs([$pf, $fdcs, $bdcs, $vgcs, $wgcs, $igcs, $fgcs, $rdcs, $ccs])
            ->setMethods(['__toString'])
            ->getMock();
    }

    public function testParsingWithFromName() : void
    {
        $header = new ReceivedHeader($this->consumerService, 'Received', 'From JonSnow');
        $this->assertEquals('JonSnow', $header->getFromName());
        $this->assertNull($header->getFromHostname());
        $this->assertNull($header->getFromAddress());
        $this->assertNull($header->getByHostname());
        $this->assertNull($header->getByAddress());
        $this->assertNull($header->getDateTime());
    }

    public function testParsingFromExtended() : void
    {
        $header = new ReceivedHeader($this->consumerService, 'Received', 'FROM JonSnow (domain.com [1.2.3.4]) (Crow Crow)');
        $this->assertEquals('JonSnow', $header->getFromName());
        $this->assertEquals('domain.com', $header->getFromHostname());
        $this->assertEquals('1.2.3.4', $header->getFromAddress());
        $this->assertCount(1, $header->getComments());
        $this->assertEquals('Crow Crow', $header->getComments()[0]);
    }

    public function testParsingByExtended() : void
    {
        $header = new ReceivedHeader($this->consumerService, 'Received', 'FROM JonSnow by Ygritte.local (name.com [1.2.3.4])');
        $this->assertEquals('JonSnow', $header->getFromName());
        $this->assertNull($header->getFromHostname());
        $this->assertNull($header->getFromAddress());
        $this->assertEquals('Ygritte.local', $header->getByName());
        $this->assertEquals('name.com', $header->getByHostname());
        $this->assertEquals('1.2.3.4', $header->getByAddress());
    }

    public function testParsingWithMissingDomainParts() : void
    {
        $header = new ReceivedHeader($this->consumerService, 'Received', 'with TEST; Wed, 17 May 2000 19:08:29 -0400');
        $this->assertEquals('TEST', $header->getValueFor('WITH'));
        $dt = $header->getDateTime();
        $this->assertNotNull($dt);
        $this->assertEquals('2000-05-17T19:08:29-04:00', $dt->format(DateTime::RFC3339));
    }

    public function testParsingWithFullExampleLine() : void
    {
        $value = "FROM LeComputer (blah.host) by MyComputer ([1.2.2.2]) WITH\n"
            . 'ESMTP (TLS BLAH) ID 123; Wed, 17 May 2000 19:08:29 -0400';
        $header = new ReceivedHeader($this->consumerService, 'Received', $value);

        $this->assertEquals('LeComputer', $header->getFromName());
        $this->assertEquals('blah.host', $header->getFromHostname());
        $this->assertNull($header->getFromAddress());

        $this->assertEquals('MyComputer', $header->getByName());
        $this->assertNull($header->getByHostname());
        $this->assertEquals('1.2.2.2', $header->getByAddress());

        $this->assertEquals('ESMTP', $header->getValueFor('WITH'));
        $this->assertEquals('123', $header->getValueFor('id'));
        $this->assertNull($header->getValueFor('for'));

        $dt = $header->getDateTime();
        $this->assertNotNull($dt);
        $this->assertEquals('2000-05-17T19:08:29-04:00', $dt->format(DateTime::RFC3339));
    }

    public function testParsingWithSubConsumerNames() : void
    {
        $value = "from domain.example.id ([111.222.333.444])\n"
            . "by mail.jediforce.example.com with esmtps (TLS1.2) tls TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256\n"
            . "(Exim 4.94.2)\n"
            . "(envelope-from <noreply@domain.example.idd>)\n"
            . "id unique-string\n"
            . 'for i.am.your.father@jediforce.example.com; Sun, 28 Nov 2021 16:54:15 +0100';
        $header = new ReceivedHeader($this->consumerService, 'Received', $value);

        $this->assertEquals('domain.example.id', $header->getFromName());
        $this->assertEquals('111.222.333.444', $header->getFromAddress());

        $this->assertEquals('mail.jediforce.example.com', $header->getByName());
        $this->assertEquals('esmtps tls TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256', $header->getValueFor('with'));
        $this->assertEquals('unique-string', $header->getValueFor('id'));

        $this->assertEquals('i.am.your.father@jediforce.example.com', $header->getValueFor('for'));

        $dt = $header->getDateTime();
        $this->assertNotNull($dt);
    }
}
