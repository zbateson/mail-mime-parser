<?php
namespace ZBateson\MailMimeParser\Parser\Proxy;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7\Utils;

/**
 * ParserMessageFactoryTest
 *
 * @group ParserMessageFactory
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\Proxy\ParserMessageFactory
 * @author Zaahid Bateson
 */
class ParserMessageFactoryTest extends TestCase
{
    private $instance;
    private $streamFactory;
    private $headerContainerFactory;
    private $partStreamContainerFactory;
    private $partChildrenContainerFactory;
    private $mimeParserFactory;
    private $nonMimeParserFactory;
    private $multipartHelper;
    private $privacyHelper;

    private $headerContainer;
    private $partBuilder;
    private $partStreamContainer;
    private $partChildrenContainer;
    private $parser;
    
    protected function legacySetUp()
    {
        $this->streamFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerContainerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partChildrenContainerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mimeParserFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\MimeParserFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->nonMimeParserFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\NonMimeParserFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->multipartHelper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MultipartHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->privacyHelper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\PrivacyHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->headerContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partChildrenContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockForAbstractClass('ZBateson\MailMimeParser\Parser\IParser');

        $this->instance = new ParserMessageFactory(
            $this->streamFactory,
            $this->headerContainerFactory,
            $this->partStreamContainerFactory,
            $this->partChildrenContainerFactory,
            $this->mimeParserFactory,
            $this->nonMimeParserFactory,
            $this->multipartHelper,
            $this->privacyHelper
        );
    }

    public function testNewInstance()
    {
        $this->headerContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->headerContainer)
            ->willReturn($this->headerContainer);
        $this->mimeParserFactory->expects($this->once())
            ->method('canParse')
            ->with($this->headerContainer)
            ->willReturn(true);
        $this->mimeParserFactory->expects($this->once())
            ->method('newInstance')
            ->willReturn($this->parser);
        $this->nonMimeParserFactory->expects($this->never())
            ->method('canParse');
        $this->nonMimeParserFactory->expects($this->never())
            ->method('newInstance');
        $this->partStreamContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy'))
            ->willReturn($this->partStreamContainer);
        $this->partChildrenContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy'))
            ->willReturn($this->partChildrenContainer);
        $stream = Utils::streamFor('test');
        $this->streamFactory->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\IMessage'))
            ->willReturn($stream);
        $this->partStreamContainer->expects($this->once())
            ->method('setStream')
            ->with($stream);

        $ob = $this->instance->newInstance($this->partBuilder, $this->headerContainer);
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy',
            $ob
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\IMessage',
            $ob->getPart()
        );
    }

    public function testNewInstanceNonMimeParser()
    {
        $this->headerContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->headerContainer)
            ->willReturn($this->headerContainer);
        $this->mimeParserFactory->expects($this->once())
            ->method('canParse')
            ->with($this->headerContainer)
            ->willReturn(false);
        $this->mimeParserFactory->expects($this->never())
            ->method('newInstance');
        $this->nonMimeParserFactory->expects($this->once())
            ->method('canParse')
            ->with($this->headerContainer)
            ->willReturn(true);
        $this->nonMimeParserFactory->expects($this->once())
            ->method('newInstance')
            ->willReturn($this->parser);
        $this->partStreamContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy'))
            ->willReturn($this->partStreamContainer);
        $this->partChildrenContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy'))
            ->willReturn($this->partChildrenContainer);
        $stream = Utils::streamFor('test');
        $this->streamFactory->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\IMessage'))
            ->willReturn($stream);
        $this->partStreamContainer->expects($this->once())
            ->method('setStream')
            ->with($stream);

        $ob = $this->instance->newInstance($this->partBuilder, $this->headerContainer);
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy',
            $ob
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\IMessage',
            $ob->getPart()
        );
    }

    public function testPrependParserFactory()
    {
        $pf = $this->getMockForAbstractClass('ZBateson\MailMimeParser\Parser\IParserFactory');
        $this->instance->prependMessageParserFactory($pf);

        $this->headerContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->headerContainer)
            ->willReturn($this->headerContainer);
        $pf->expects($this->once())
            ->method('canParse')
            ->with($this->headerContainer)
            ->willReturn(true);
        $pf->expects($this->once())
            ->method('newInstance')
            ->willReturn($this->parser);
        $this->mimeParserFactory->expects($this->never())
            ->method('canParse');
        $this->nonMimeParserFactory->expects($this->never())
            ->method('canParse');
        $this->partStreamContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy'))
            ->willReturn($this->partStreamContainer);
        $this->partChildrenContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy'))
            ->willReturn($this->partChildrenContainer);
        $stream = Utils::streamFor('test');
        $this->streamFactory->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\IMessage'))
            ->willReturn($stream);
        $this->partStreamContainer->expects($this->once())
            ->method('setStream')
            ->with($stream);

        $ob = $this->instance->newInstance($this->partBuilder, $this->headerContainer);
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy',
            $ob
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\IMessage',
            $ob->getPart()
        );
    }
}
