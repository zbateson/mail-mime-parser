<?php

namespace ZBateson\MailMimeParser\Message\Helper;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use RecursiveArrayIterator;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Stream\MessagePartStreamDecorator;

/**
 * GenericHelperTest
 *
 * @group GenericHelper
 * @group MessageHelper
 * @covers ZBateson\MailMimeParser\Message\Helper\AbstractHelper
 * @covers ZBateson\MailMimeParser\Message\Helper\GenericHelper
 * @author Zaahid Bateson
 */
class GenericHelperTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mockMimePartFactory;

    // @phpstan-ignore-next-line
    private $mockUUEncodedPartFactory;

    protected function setUp() : void
    {
        $this->mockMimePartFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Factory\IMimePartFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUUEncodedPartFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Factory\IUUEncodedPartFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newMockIMimePart() : \ZBateson\MailMimeParser\Message\IMimePart
    {
        return $this
            ->getMockBuilder(\ZBateson\MailMimeParser\Message\MimePart::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newMockIMessage() : \ZBateson\MailMimeParser\IMessage
    {
        return $this
            ->getMockBuilder(\ZBateson\MailMimeParser\Message::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newGenericHelper() : GenericHelper
    {
        return new GenericHelper($this->mockMimePartFactory, $this->mockUUEncodedPartFactory);
    }

    public function testCopyHeaders() : void
    {
        $helper = $this->newGenericHelper();
        $from = $this->newMockIMimePart();
        $to = $this->newMockIMimePart();

        $mockHeader = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\GenericHeader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $from->expects($this->once())
            ->method('getHeader')
            ->with('test')
            ->willReturn($mockHeader);
        $mockHeader->expects($this->once())
            ->method('getRawValue')
            ->willReturn('value');
        $to->expects($this->once())
            ->method('setRawHeader')
            ->with('test', 'value');

        $helper->copyHeader($from, $to, 'test');
    }

    public function testRemoveContentHeadersAndContent() : void
    {
        $helper = $this->newGenericHelper();
        $part = $this->newMockIMimePart();

        $names = ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition',
            'Content-ID', 'Content-Description', 'Content-Language', 'Content-Base',
            'Content-Location', 'Content-Features', 'Content-Alternative',
            'Content-MD5', 'Content-Duration', 'Something-Else', 'Content-Return'];
        $aHeaders = [];
        foreach ($names as $name) {
            $mock = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\GenericHeader::class)
                ->disableOriginalConstructor()
                ->getMock();
            $mock->method('getName')->willReturn($name);
            $aHeaders[] = $mock;
        }

        $part->expects($this->once())
            ->method('getAllHeaders')
            ->willReturn($aHeaders);

        $part->expects($this->exactly(12))
            ->method('removeHeader')
            ->withConsecutive(
                ['Content-Type'],
                ['Content-Transfer-Encoding'],
                ['Content-Disposition'],
                ['Content-ID'],
                ['Content-Description'],
                ['Content-Language'],
                ['Content-Base'],
                ['Content-Location'],
                ['Content-Features'],
                ['Content-Alternative'],
                ['Content-MD5'],
                ['Content-Duration']
            );
        $part->expects($this->once())
            ->method('detachContentStream');

        $helper->removeContentHeadersAndContent($part);
    }

    public function testCopyContentHeadersAndContent() : void
    {
        $helper = $this->newGenericHelper();

        $from = $this->newMockIMimePart();
        $to = $this->newMockIMimePart();

        $names = ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition',
            'Content-ID', 'Content-Description', 'Content-Language', 'Content-Base',
            'Content-Location', 'Content-Features', 'Content-Alternative',
            'Content-MD5', 'Content-Duration', 'Something-Else', 'Content-Return'];
        $aHeaders = [];
        $returnMap = [];
        foreach ($names as $name) {
            $mock = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\GenericHeader::class)
                ->disableOriginalConstructor()
                ->getMock();
            $mock->method('getName')->willReturn($name);
            $aHeaders[] = $mock;
            $returnMap[] = [$name, $mock];
        }

        $fromStream = $this->getMockBuilder(MessagePartStreamDecorator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $from->expects($this->once())
            ->method('hasContent')
            ->willReturn(true);
        $from->expects($this->once())
            ->method('getContentStream')
            ->willReturn($fromStream);

        $from->method('getHeader')
            ->willReturnMap($returnMap);
        $from->method('getAllHeaders')
            ->willReturn($aHeaders);

        $to->expects($this->once())
            ->method('attachContentStream')
            ->with($fromStream, MailMimeParser::DEFAULT_CHARSET);

        $from->expects($this->never())
            ->method('removeHeader');

        $helper->copyContentHeadersAndContent($from, $to);
    }

    public function testCreateNewContentPartFrom() : void
    {
        $helper = $this->newGenericHelper();

        $from = $this->newMockIMimePart();
        $to = $this->newMockIMimePart();

        $names = ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition',
            'Content-ID', 'Content-Description', 'Content-Language', 'Content-Base',
            'Content-Location', 'Content-Features', 'Content-Alternative',
            'Content-MD5', 'Content-Duration', 'Something-Else', 'Content-Return'];
        $aHeaders = [];
        $returnMap = [];
        foreach ($names as $name) {
            $mock = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\GenericHeader::class)
                ->disableOriginalConstructor()
                ->getMock();
            $mock->method('getName')->willReturn($name);
            $aHeaders[] = $mock;
            $returnMap[] = [$name, $mock];
        }

        $mockPart = $this->newMockIMimePart();
        $this->mockMimePartFactory
            ->expects($this->once())
            ->method('newInstance')
            ->willReturn($mockPart);

        $from->expects($this->once())
            ->method('hasContent')
            ->willReturn(false);

        $from->method('getHeader')
            ->willReturnMap($returnMap);
        $from->method('getAllHeaders')
            ->willReturn($aHeaders);

        $to->expects($this->never())
            ->method('attachContentStream');
        $from->expects($this->exactly(12))
            ->method('removeHeader');

        $helper->createNewContentPartFrom($from);
    }

    public function testMovePartContentAndChildrenWithReplacePart() : void
    {
        $helper = $this->newGenericHelper();

        $from = $this->newMockIMessage();
        $to = $this->newMockIMimePart();

        $child1 = $this->newMockIMimePart();
        $child2 = $this->newMockIMimePart();

        $names = ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition',
            'Content-ID', 'Content-Description', 'Content-Language', 'Content-Base',
            'Content-Location', 'Content-Features', 'Content-Alternative',
            'Content-MD5', 'Content-Duration', 'Something-Else', 'Content-Return'];
        $aHeaders = [];
        $returnMap = [];
        foreach ($names as $name) {
            $mock = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\GenericHeader::class)
                ->disableOriginalConstructor()
                ->getMock();
            $mock->method('getName')->willReturn($name);
            $aHeaders[] = $mock;
            $returnMap[] = [$name, $mock];
        }

        $toStream = $this->getMockBuilder(MessagePartStreamDecorator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $to->expects($this->once())
            ->method('hasContent')
            ->willReturn(true);
        $to->expects($this->once())
            ->method('getContentStream')
            ->willReturn($toStream);

        $to->expects($this->once())
            ->method('getChildIterator')
            ->willReturn(new RecursiveArrayIterator([$child1, $child2]));
        $to->expects($this->once())
            ->method('getChildCount')
            ->willReturn(2);

        $from->expects($this->exactly(1))
            ->method('removePart')
            ->withConsecutive([$to]);

        $to->method('getHeader')
            ->willReturnMap($returnMap);
        $to->method('getAllHeaders')
            ->willReturn($aHeaders);

        $from->expects($this->once())
            ->method('attachContentStream')
            ->with($toStream, MailMimeParser::DEFAULT_CHARSET);

        $from->expects($this->exactly(2))
            ->method('addChild')
            ->withConsecutive([$child1], [$child2]);

        $to->expects($this->exactly(12))
            ->method('removeHeader');

        $helper->replacePart($from, $from, $to);
    }

    public function testReplacePart() : void
    {
        $helper = $this->newGenericHelper();

        $message = $this->newMockIMessage();
        $part = $this->newMockIMimePart();
        $rep = $this->newMockIMimePart();

        $part->expects($this->once())
            ->method('getParent')
            ->willReturn($message);
        $message->expects($this->exactly(2))
            ->method('removePart')
            ->withConsecutive([$rep], [$part])
            ->willReturn(10);
        $message->expects($this->once())
            ->method('addChild')
            ->with($rep, 10);

        $helper->replacePart($message, $part, $rep);
    }
}
