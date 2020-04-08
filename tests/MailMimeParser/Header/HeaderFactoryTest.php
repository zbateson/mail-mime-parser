<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit\Framework\TestCase;

/**
 * Description of HeaderFactoryTest
 *
 * @group Headers
 * @group HeaderFactory
 * @covers ZBateson\MailMimeParser\Header\HeaderFactory
 * @author Zaahid Bateson
 */
class HeaderFactoryTest extends TestCase
{
    protected $headerFactory;

    protected function setUp()
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
        $this->headerFactory = new HeaderFactory($cs, $mlpf);
    }

    public function testAddressHeaderInstance()
    {
        $aValid = ['BCC', 'to', 'FrOM'];
        $aNot = ['MESSAGE-ID', 'date', 'Subject'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\AddressHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\AddressHeader', get_class($header));
        }
    }

    public function testDateHeaderInstance()
    {
        $aValid = ['Date', 'ExpIRY-Date', 'EXPIRES'];
        $aNot = ['MESSAGE-ID', 'bcc', 'Subject'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Wed, 17 May 2000 19:08:29 -0400');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\DateHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\DateHeader', get_class($header));
        }
    }

    public function testGenericHeaderInstance()
    {
        $aValid = ['X-Generic-Header', 'Authentication-Results'];
        $aNot = ['BCC', 'ExPirY-daTE', 'Content-DISPOSITION', 'Subject', 'Content-ID', 'Message-ID', 'References', 'Received'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\GenericHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\GenericHeader', get_class($header));
        }
    }

    public function testIdHeaderInstance()
    {
        $aValid = ['Content-ID', 'Message-ID', 'In-Reply-To', 'References'];
        $aNot = ['BCC', 'ExPirY-daTE', 'Content-DISPOSITION', 'Subject', 'X-Generic-Header', 'Received', 'Authentication-Results'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\IdHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\IdHeader', get_class($header));
        }
    }

    public function testSubjectHeaderInstance()
    {
        $aValid = ['Subject'];
        $aNot = ['BCC', 'ExPirY-daTE', 'Content-DISPOSITION', 'Content-Id', 'content-ID', 'IN-REPLY-TO'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\SubjectHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\SubjectHeader', get_class($header));
        }
    }

    public function testParameterHeaderInstance()
    {
        $aValid = ['Content-Type', 'CONTENT-Disposition'];
        $aNot = ['MESSAGE-ID', 'bcc', 'Subject', 'X-Header-Test'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\ParameterHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\ParameterHeader', get_class($header));
        }
    }

    public function testReceivedHeaderInstance()
    {
        $aValid = ['Received'];
        $aNot = ['BCC', 'ExPirY-daTE', 'Content-DISPOSITION', 'Subject', 'X-Generic-Header', 'Authentication-Results', 'In-Reply-To', 'References', 'Message-ID'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\ReceivedHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\ReceivedHeader', get_class($header));
        }
    }

    public function testHeaderContainer()
    {
        $this->assertInstanceOf(
            'ZBateson\MailMimeParser\Header\HeaderContainer',
            $this->headerFactory->newHeaderContainer()
        );
    }
}
