<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of HeaderPartFactoryTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(HeaderPartFactory::class)]
#[Group('HeaderParts')]
#[Group('HeaderPartFactory')]
class HeaderPartFactoryTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mb;

    private $headerPartFactory;

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $this->mb = new MbWrapper();
        $this->headerPartFactory = new HeaderPartFactory($this->logger, $this->mb);
    }

    private function getTokenArray(string $name) : array
    {
        return [$this->getMockBuilder(Token::class)
            ->setConstructorArgs([$this->logger, $this->mb, $name])
            ->onlyMethods([])
            ->getMock()];
    }

    public function testNewInstance() : void
    {
        $token = $this->headerPartFactory->newInstance('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\\' . Token::class, $token);
    }

    public function testNewToken() : void
    {
        $token = $this->headerPartFactory->newToken('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\\' . Token::class, $token);
    }

    public function testNewSplitParameterToken() : void
    {
        $param = [$this->getMockBuilder(ParameterPart::class)
            ->setConstructorArgs([$this->logger, $this->mb, $this->getTokenArray('Test'), $this->headerPartFactory->newContainerPart($this->getTokenArray('Value'))])
            ->onlyMethods([])
            ->getMock()];
        $token = $this->headerPartFactory->newSplitParameterPart($param);
        $this->assertNotNull($token);
        $this->assertInstanceOf('\\' . SplitParameterPart::class, $token);
    }

    public function testNewContainerPart() : void
    {
        $part = $this->headerPartFactory->newContainerPart($this->getTokenArray('Test'));
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . ContainerPart::class, $part);
    }

    public function testNewMimeToken() : void
    {
        $part = $this->headerPartFactory->newMimeToken('Test');
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . MimeToken::class, $part);
    }

    public function testNewCommentPart() : void
    {
        $part = $this->headerPartFactory->newCommentPart($this->getTokenArray('Test'));
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . CommentPart::class, $part);
    }

    public function testNewAddress() : void
    {
        $part = $this->headerPartFactory->newAddress($this->getTokenArray('Test'), $this->getTokenArray('Test'));
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . AddressPart::class, $part);
    }

    public function testNewAddressGroupPart() : void
    {
        $part = $this->headerPartFactory->newAddressGroupPart($this->getTokenArray('Test'), $this->getTokenArray('Test'));
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . AddressGroupPart::class, $part);
    }

    public function testNewDatePart() : void
    {
        $part = $this->headerPartFactory->newDatePart($this->getTokenArray('Test'));
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . DatePart::class, $part);
    }

    public function testNewParameterPart() : void
    {
        $part = $this->headerPartFactory->newParameterPart($this->getTokenArray('Test'), $this->headerPartFactory->newContainerPart($this->getTokenArray('Test')));
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . ParameterPart::class, $part);
    }

    public function testNewReceivedPart() : void
    {
        $part = $this->headerPartFactory->newReceivedPart('Test', $this->getTokenArray('Value'));
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . ReceivedPart::class, $part);
        $this->assertEquals('Test', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }

    public function testNewReceivedDomainPart() : void
    {
        $part = $this->headerPartFactory->newReceivedDomainPart('Test', $this->getTokenArray('Value'));
        $this->assertNotNull($part);
        $this->assertInstanceOf('\\' . ReceivedDomainPart::class, $part);
        $this->assertEquals('Test', $part->getName());
        $this->assertEquals('Value', $part->getValue());
    }
}
