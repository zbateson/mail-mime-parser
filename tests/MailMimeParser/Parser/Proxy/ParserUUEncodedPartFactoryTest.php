<?php
namespace ZBateson\MailMimeParser\Parser\Proxy;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7\Utils;

/**
 * ParserUUEncodedPartFactoryTest
 *
 * @group ParserUUEncodedPartFactory
 * @group Parser
 * @covers ZBateson\MailMimeParser\Parser\Proxy\ParserUUEncodedPartFactory
 * @author Zaahid Bateson
 */
class ParserUUEncodedPartFactoryTest extends TestCase
{
    private $instance;
    private $streamFactory;
    private $partStreamContainerFactory;

    private $partBuilder;
    private $partStreamContainer;
    private $parent;

    protected function legacySetUp()
    {
        $this->streamFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainerFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->partStreamContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainer')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->parent = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $this->instance = new ParserUUEncodedPartFactory(
            $this->streamFactory,
            $this->partStreamContainerFactory
        );
    }

    public function testNewInstance()
    {
        $this->partStreamContainerFactory->expects($this->once())
            ->method('newInstance')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy'))
            ->willReturn($this->partStreamContainer);
        $stream = Utils::streamFor('test');
        $this->streamFactory->expects($this->once())
            ->method('newMessagePartStream')
            ->with($this->isInstanceOf('\ZBateson\MailMimeParser\Message\IUUEncodedPart'))
            ->willReturn($stream);
        $this->partStreamContainer->expects($this->once())
            ->method('setStream')
            ->with($stream);
        $this->parent->expects($this->once())
            ->method('getPart')
            ->willReturn($this->getMockForAbstractClass('ZBateson\MailMimeParser\Message\IMimePart'));

        $ob = $this->instance->newInstance($this->partBuilder, 0644, 'test-file.ext', $this->parent);
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy',
            $ob
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\IUUEncodedPart',
            $ob->getPart()
        );
        $this->assertSame(0644, $ob->getPart()->getUnixFileMode());
        $this->assertSame('test-file.ext', $ob->getPart()->getFilename());
    }
}
