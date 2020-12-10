<?php
namespace ZBateson\MailMimeParser\Message\Helper;

use GuzzleHttp\Psr7;
use ZBateson\MailMimeParser\MailMimeParser;
use LegacyPHPUnit\TestCase;

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
    private $mockMimePartFactory;
    private $mockUUEncodedPartFactory;
    private $mockPartBuilderFactory;

    protected function legacySetUp()
    {
        $this->mockMimePartFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\MimePartFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUUEncodedPartFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\UUEncodedPartFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartBuilderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\PartBuilderFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newMockMimePart()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newMockMessage()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Message')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newGenericHelper()
    {
        return new GenericHelper($this->mockMimePartFactory, $this->mockUUEncodedPartFactory, $this->mockPartBuilderFactory);
    }

    public function testCopyHeaders()
    {
        $helper = $this->newGenericHelper();
        $from = $this->newMockMimePart();
        $to = $this->newMockMimePart();

        $mockHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\GenericHeader')
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

    public function testRemoveContentHeadersAndContent()
    {
        $helper = $this->newGenericHelper();
        $part = $this->newMockMimePart();

        $part->expects($this->exactly(12))
            ->method('removeHeader')
            ->withConsecutive(
                [ 'Content-Type' ],
                [ 'Content-Transfer-Encoding' ],
                [ 'Content-Disposition' ],
                [ 'Content-ID' ],
                [ 'Content-Description' ],
                [ 'Content-Language' ],
                [ 'Content-Base' ],
                [ 'Content-Location' ],
                [ 'Content-features' ],
                [ 'Content-Alternative' ],
                [ 'Content-MD5' ],
                [ 'Content-Duration' ]
            );
        $part->expects($this->once())
            ->method('detachContentStream');

        $helper->removeContentHeadersAndContent($part);
    }

    public function testCopyContentHeadersAndContent()
    {
        $helper = $this->newGenericHelper();

        $from = $this->newMockMimePart();
        $to = $this->newMockMimePart();

        $mockHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\GenericHeader')
            ->disableOriginalConstructor()
            ->getMock();

        $fromStream = Psr7\stream_for('test');
        $from->expects($this->once())
            ->method('hasContent')
            ->willReturn(true);
        $from->expects($this->once())
            ->method('getContentStream')
            ->willReturn($fromStream);

        $from->expects($this->exactly(13))
            ->method('getHeader')
            ->withConsecutive(
                [ 'Content-Type' ],
                [ 'Content-Type' ],
                [ 'Content-Transfer-Encoding' ],
                [ 'Content-Disposition' ],
                [ 'Content-ID' ],
                [ 'Content-Description' ],
                [ 'Content-Language' ],
                [ 'Content-Base' ],
                [ 'Content-Location' ],
                [ 'Content-features' ],
                [ 'Content-Alternative' ],
                [ 'Content-MD5' ],
                [ 'Content-Duration' ]
            )
            ->willReturnOnConsecutiveCalls(
                $mockHeader, $mockHeader, null
            );

        $to->expects($this->once())
            ->method('attachContentStream')
            ->with($fromStream, MailMimeParser::DEFAULT_CHARSET);

        $from->expects($this->never())
            ->method('removeHeader');

        $helper->copyContentHeadersAndContent($from, $to);
    }

    public function testCreateNewContentPartFrom()
    {
        $helper = $this->newGenericHelper();

        $from = $this->newMockMimePart();
        $to = $this->newMockMimePart();

        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartBuilderFactory->expects($this->once())
            ->method('newPartBuilder')
            ->with($this->mockMimePartFactory)
            ->willReturn(
                $partBuilder
            );
        $partBuilder->expects($this->once())
            ->method('createMessagePart')
            ->willReturn($to);

        $from->expects($this->once())
            ->method('hasContent')
            ->willReturn(false);

        $from->expects($this->exactly(13))
            ->method('getHeader')
            ->withConsecutive(
                [ 'Content-Type' ],
                [ 'Content-Type' ],
                [ 'Content-Transfer-Encoding' ],
                [ 'Content-Disposition' ],
                [ 'Content-ID' ],
                [ 'Content-Description' ],
                [ 'Content-Language' ],
                [ 'Content-Base' ],
                [ 'Content-Location' ],
                [ 'Content-features' ],
                [ 'Content-Alternative' ],
                [ 'Content-MD5' ],
                [ 'Content-Duration' ]
            )
            ->willReturn(null);

        $to->expects($this->never())
            ->method('attachContentStream');
        $from->expects($this->exactly(12))
            ->method('removeHeader');

        $helper->createNewContentPartFrom($from);
    }

    public function testMovePartContentAndChildrenWithReplacePart()
    {
        $helper = $this->newGenericHelper();

        $from = $this->newMockMessage();
        $to = $this->newMockMimePart();

        $child1 = $this->newMockMimePart();
        $child2 = $this->newMockMimePart();

        $mockHeader = $this->getMockBuilder('ZBateson\MailMimeParser\Header\GenericHeader')
            ->disableOriginalConstructor()
            ->getMock();

        $toStream = Psr7\stream_for('test');
        $to->expects($this->once())
            ->method('hasContent')
            ->willReturn(true);
        $to->expects($this->once())
            ->method('getContentStream')
            ->willReturn($toStream);

        $to->expects($this->once())
            ->method('getChildParts')
            ->willReturn([ $child1, $child2 ]);

        $from->expects($this->exactly(1))
            ->method('removePart')
            ->withConsecutive([$to]);

        $to->expects($this->exactly(13))
            ->method('getHeader')
            ->withConsecutive(
                [ 'Content-Type' ],
                [ 'Content-Type' ],
                [ 'Content-Transfer-Encoding' ],
                [ 'Content-Disposition' ],
                [ 'Content-ID' ],
                [ 'Content-Description' ],
                [ 'Content-Language' ],
                [ 'Content-Base' ],
                [ 'Content-Location' ],
                [ 'Content-features' ],
                [ 'Content-Alternative' ],
                [ 'Content-MD5' ],
                [ 'Content-Duration' ]
            )
            ->willReturnOnConsecutiveCalls(
                $mockHeader, $mockHeader, null
            );

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

    public function testReplacePart()
    {
        $helper = $this->newGenericHelper();

        $message = $this->newMockMessage();
        $part = $this->newMockMimePart();
        $rep = $this->newMockMimePart();

        $part->expects($this->once())
            ->method('getParent')
            ->willReturn($message);
        $message->expects($this->once())
            ->method('removePart')
            ->with($part)
            ->willReturn(10);
        $message->expects($this->once())
            ->method('addChild')
            ->with($rep, 10);

        $helper->replacePart($message, $part, $rep);
    }
}
