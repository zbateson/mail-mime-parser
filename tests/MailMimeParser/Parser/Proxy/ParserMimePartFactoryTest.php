<?php
namespace ZBateson\MailMimeParser\Parser\Proxy;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7\Utils;

/**
 * ParserMimePartFactoryTest
 *
 * @group ParserMimePartFactory
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartFactory
 * @author Zaahid Bateson
 */
class ParserMimePartFactoryTest extends TestCase
{
    private $instance;
    private $streamFactory;
    private $headerContainerFactory;
    private $partStreamContainerFactory;
    private $partChildrenContainerFactory;
    private $parserFactory;

    private $headerContainer;
    private $partBuilder;
    private $partStreamContainer;
    private $partChildrenContainer;
    private $parser;
    private $parent;

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
        $this->parserFactory = $this->getMockForAbstractClass('ZBateson\MailMimeParser\Parser\IParserFactory');

        $this->headerContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partChildrenContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockForAbstractClass('ZBateson\MailMimeParser\Parser\IParser');

        $this->parent = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new ParserMimePartFactory(
            $this->streamFactory,
            $this->headerContainerFactory,
            $this->partStreamContainerFactory,
            $this->partChildrenContainerFactory
        );
        $this->instance->setParserFactory($this->parserFactory);
    }

    public function testNewInstance()
    {
        $this->headerContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->headerContainer)
            ->willReturn($this->headerContainer);
        $this->parserFactory->expects($this->once())
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
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Message\IMimePart'))
            ->willReturn($stream);
        $this->partStreamContainer->expects($this->once())
            ->method('setStream')
            ->with($stream);
        $this->parent->expects($this->once())
            ->method('getPart')
            ->willReturn($this->getMockForAbstractClass('ZBateson\MailMimeParser\Message\IMimePart'));

        $ob = $this->instance->newInstance($this->partBuilder, $this->headerContainer, $this->parent);
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy',
            $ob
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\IMimePart',
            $ob->getPart()
        );
    }
}
