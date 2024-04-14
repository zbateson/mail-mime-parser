<?php

namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ZBateson\MailMimeParser\Header\Consumer\AddressBaseConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\AddressConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\AddressEmailConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\AddressGroupConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\DateConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\GenericConsumerMimeLiteralPartService;
use ZBateson\MailMimeParser\Header\Consumer\IdBaseConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\IdConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ParameterConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ParameterValueConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ParameterNameValueConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ReceivedConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\SubjectConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\ReceivedDateConsumerService;

/**
 * Description of HeaderFactoryTest
 *
 * @group Headers
 * @group HeaderFactory
 * @covers ZBateson\MailMimeParser\Header\HeaderFactory
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class HeaderFactoryTest extends TestCase
{
    // @phpstan-ignore-next-line
    protected $headerFactory;
    private $logger;

    protected function setUp() : void
    {
        $this->logger = new NullLogger();
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


        $dcs = $this->getMockBuilder(DateConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, $qscs])
            ->setMethods()
            ->getMock();
        $gcmlpcs = $this->getMockBuilder(GenericConsumerMimeLiteralPartService::class)
            ->setConstructorArgs([$this->logger, $mpf, $ccs, $qscs])
            ->setMethods()
            ->getMock();

        $idcs = $this->getMockBuilder(IdConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, $qscs])
            ->setMethods()
            ->getMock();
        $idbcs = $this->getMockBuilder(IdBaseConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, $qscs, $idcs])
            ->setMethods()
            ->getMock();

        $pvcs = $this->getMockBuilder(ParameterValueConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $ccs, $qscs])
            ->setMethods()
            ->getMock();
        $pnvcs = $this->getMockBuilder(ParameterNameValueConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $pvcs, $ccs, $qscs])
            ->setMethods()
            ->getMock();
        $pcs = $this->getMockBuilder(ParameterConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $pnvcs, $ccs, $qscs])
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

        $rcs = $this->getMockBuilder(ReceivedConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $fdcs, $bdcs, $vgcs, $wgcs, $igcs, $fgcs, $rdcs, $ccs])
            ->setMethods()
            ->getMock();
        $scs = $this->getMockBuilder(SubjectConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf])
            ->setMethods()
            ->getMock();
        
        $agcs = $this->getMockBuilder(AddressGroupConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->setMethods()
            ->getMock();
        $aecs = $this->getMockBuilder(AddressEmailConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $ccs, $qscs])
            ->setMethods()
            ->getMock();
        $acs = $this->getMockBuilder(AddressConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $agcs, $aecs, $ccs, $qscs])
            ->setMethods()
            ->getMock();
        $abcs = $this->getMockBuilder(AddressBaseConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf, $acs])
            ->setMethods()
            ->getMock();

        $this->headerFactory = new HeaderFactory(
            $this->logger, $mpf, $abcs, $dcs, $gcmlpcs, $idbcs, $pcs, $rcs, $scs
        );
    }

    public function testAddressHeaderInstance() : void
    {
        $aValid = ['BCC', 'to', 'FrOM', 'sender', 'reply-to', 'resent-from', 'Resent-To', 'Resent-Cc', 'Resent-Bcc', 'Resent-Reply-To', 'Return-Path', 'Delivered-To'];
        $aNot = ['MESSAGE-ID', 'date', 'Subject'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertInstanceOf(\ZBateson\MailMimeParser\Header\AddressHeader::class, $header);
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotInstanceOf(\ZBateson\MailMimeParser\Header\AddressHeader::class, $header);
        }
    }

    public function testDateHeaderInstance() : void
    {
        $aValid = ['Date', 'ExpIRY-Date', 'EXPIRES'];
        $aNot = ['MESSAGE-ID', 'bcc', 'Subject'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Wed, 17 May 2000 19:08:29 -0400');
            $this->assertNotNull($header);
            $this->assertInstanceOf(\ZBateson\MailMimeParser\Header\DateHeader::class, $header);
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotInstanceOf(\ZBateson\MailMimeParser\Header\DateHeader::class, $header);
        }
    }

    public function testGenericHeaderInstance() : void
    {
        $aValid = ['X-Generic-Header', 'Some-Other-Header'];
        $aNot = ['BCC', 'ExPirY-daTE', 'Content-DISPOSITION', 'Subject', 'Content-ID', 'Message-ID', 'References', 'Received'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertInstanceOf(\ZBateson\MailMimeParser\Header\GenericHeader::class, $header);
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotInstanceOf(\ZBateson\MailMimeParser\Header\GenericHeader::class, $header);
        }
    }

    public function testIdHeaderInstance() : void
    {
        $aValid = ['Content-ID', 'Message-ID', 'In-Reply-To', 'References'];
        $aNot = ['BCC', 'ExPirY-daTE', 'Content-DISPOSITION', 'Subject', 'X-Generic-Header', 'Received', 'Authentication-Results'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertInstanceOf(\ZBateson\MailMimeParser\Header\IdHeader::class, $header);
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotInstanceOf(\ZBateson\MailMimeParser\Header\IdHeader::class, $header);
        }
    }

    public function testSubjectHeaderInstance() : void
    {
        $aValid = ['Subject'];
        $aNot = ['BCC', 'ExPirY-daTE', 'Content-DISPOSITION', 'Content-Id', 'content-ID', 'IN-REPLY-TO'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertInstanceOf(\ZBateson\MailMimeParser\Header\SubjectHeader::class, $header);
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotInstanceOf(\ZBateson\MailMimeParser\Header\SubjectHeader::class, $header);
        }
    }

    public function testParameterHeaderInstance() : void
    {
        $aValid = ['Content-Type', 'CONTENT-Disposition'];
        $aNot = ['MESSAGE-ID', 'bcc', 'Subject', 'X-Header-Test'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertInstanceOf(\ZBateson\MailMimeParser\Header\ParameterHeader::class, $header);
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotInstanceOf(\ZBateson\MailMimeParser\Header\ParameterHeader::class, $header);
        }
    }

    public function testReceivedHeaderInstance() : void
    {
        $aValid = ['Received'];
        $aNot = ['BCC', 'ExPirY-daTE', 'Content-DISPOSITION', 'Subject', 'X-Generic-Header', 'Authentication-Results', 'In-Reply-To', 'References', 'Message-ID'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertInstanceOf(\ZBateson\MailMimeParser\Header\ReceivedHeader::class, $header);
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotInstanceOf(\ZBateson\MailMimeParser\Header\ReceivedHeader::class, $header);
        }
    }

    public function testNewInstanceOf() : void
    {
        $aHeaders = ['Subject', 'X-Subject', 'From'];
        foreach ($aHeaders as $name) {
            $header = $this->headerFactory->newInstanceOf($name, 'Test', \ZBateson\MailMimeParser\Header\ReceivedHeader::class);
            $this->assertNotNull($header);
            $this->assertInstanceOf(\ZBateson\MailMimeParser\Header\ReceivedHeader::class, $header);
        }
    }

    public function testStaticFromNameValue() : void
    {
        $header = AbstractHeader::from('Subject', 'Test');
        $this->assertInstanceOf(SubjectHeader::class, $header);
        $this->assertEquals('Subject', $header->getName());
        $this->assertEquals('Test', $header->getValue());
    }

    public function testStaticFromHeaderLine() : void
    {
        $header = AbstractHeader::from('Subject: Test');
        $this->assertInstanceOf(SubjectHeader::class, $header);
        $this->assertEquals('Subject', $header->getName());
        $this->assertEquals('Test', $header->getValue());
    }

    public function testStaticFromHeaderLineNoName() : void
    {
        $header = AbstractHeader::from('Test');
        $this->assertInstanceOf(GenericHeader::class, $header);
        $this->assertEquals('', $header->getName());
        $this->assertEquals('Test', $header->getValue());
    }

    public function testStaticFromHeaderLineMultipleColon() : void
    {
        $header = AbstractHeader::from('Subject: Test:Blah');
        $this->assertInstanceOf(SubjectHeader::class, $header);
        $this->assertEquals('Subject', $header->getName());
        $this->assertEquals('Test:Blah', $header->getValue());
    }

    public function testStaticFromSpecializedHeader() : void
    {
        $header = SubjectHeader::from('From: Test:Blah');
        $this->assertInstanceOf(SubjectHeader::class, $header);
        $this->assertEquals('From', $header->getName());
        $this->assertEquals('Test:Blah', $header->getValue());
    }
}
