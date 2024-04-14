<?php

namespace ZBateson\MailMimeParser\Header;

use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
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
        $this->consumerService = $this->getMockBuilder(ReceivedConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $fdcs, $bdcs, $vgcs, $wgcs, $igcs, $fgcs, $rdcs, $ccs])
            ->setMethods()
            ->getMock();
    }

    private function newReceivedHeader($name, $value)
    {
        return new ReceivedHeader($name, $value, $this->logger, $this->consumerService);
    }

    public function testParsingWithFromName() : void
    {
        $header = $this->newReceivedHeader('Received', 'From JonSnow');
        $this->assertEquals('JonSnow', $header->getFromName());
        $this->assertNull($header->getFromHostname());
        $this->assertNull($header->getFromAddress());
        $this->assertNull($header->getByHostname());
        $this->assertNull($header->getByAddress());
        $this->assertNull($header->getDateTime());
    }

    public function testParsingFromExtended() : void
    {
        $header = $this->newReceivedHeader('Received', 'FROM JonSnow (domain.com [1.2.3.4]) (Crow Crow)');
        $this->assertEquals('JonSnow', $header->getFromName());
        $this->assertEquals('domain.com', $header->getFromHostname());
        $this->assertEquals('1.2.3.4', $header->getFromAddress());
        $this->assertCount(1, $header->getComments());
        $this->assertEquals('Crow Crow', $header->getComments()[0]);
    }

    public function testParsingByExtended() : void
    {
        $header = $this->newReceivedHeader('Received', 'FROM JonSnow by Ygritte.local (name.com [1.2.3.4])');
        $this->assertEquals('JonSnow', $header->getFromName());
        $this->assertNull($header->getFromHostname());
        $this->assertNull($header->getFromAddress());
        $this->assertEquals('Ygritte.local', $header->getByName());
        $this->assertEquals('name.com', $header->getByHostname());
        $this->assertEquals('1.2.3.4', $header->getByAddress());
    }

    public function testParsingWithMissingDomainParts() : void
    {
        $header = $this->newReceivedHeader('Received', 'with TEST; Wed, 17 May 2000 19:08:29 -0400');
        $this->assertEquals('TEST', $header->getValueFor('WITH'));
        $dt = $header->getDateTime();
        $this->assertNotNull($dt);
        $this->assertEquals('2000-05-17T19:08:29-04:00', $dt->format(DateTime::RFC3339));
    }

    public function testParsingWithFullExampleLine() : void
    {
        $value = "FROM LeComputer (blah.host) by MyComputer ([1.2.2.2]) WITH\n"
            . 'ESMTP (TLS BLAH) ID 123; Wed, 17 May 2000 19:08:29 -0400';
        $header = $this->newReceivedHeader('Received', $value);

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
        $header = $this->newReceivedHeader('Received', $value);

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
