<?php

namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * PartFilterTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(PartFilter::class)]
#[Group('PartFilter')]
#[Group('Message')]
class PartFilterTest extends TestCase
{
    public function testAttachmentFilter() : void
    {
        $callback = PartFilter::fromAttachmentFilter();

        $part = $this->createMock(IMessagePart::class);
        $part->method('getContentType')->willReturnOnConsecutiveCalls('text/plain', 'text/plain', 'text/html', 'text/html', 'blah');
        $part->method('getContentDisposition')->willReturnOnConsecutiveCalls('inline', 'attachment', 'inline', 'attachment', 'blah');

        $this->assertFalse($callback($part));
        $this->assertTrue($callback($part));
        $this->assertFalse($callback($part));
        $this->assertTrue($callback($part));
        $this->assertTrue($callback($part));

        $part = $this->createMock(IMimePart::class);
        $part->method('getContentType')->willReturnOnConsecutiveCalls('multipart/mixed', 'multipart/alternative', 'text/html', 'blah');
        $part->method('getContentDisposition')->willReturnOnConsecutiveCalls('inline', 'attachment', 'attachment', 'blah');
        $part->method('isMultiPart')->willReturnOnConsecutiveCalls(true, true, false, false);
        $part->method('isSignaturePart')->willReturnOnConsecutiveCalls(true, false);
        // multipart without an attachment disposition
        $this->assertFalse($callback($part));
        // multipart with an attachment disposition is the attachment itself
        $this->assertTrue($callback($part));
        // signature part
        $this->assertFalse($callback($part));
        $this->assertTrue($callback($part));
    }

    private function newAttachedMultipartParent() : IMimePart
    {
        $parent = $this->createMock(IMimePart::class);
        $parent->method('isMultiPart')->willReturn(true);
        $parent->method('getContentDisposition')->willReturn('attachment');
        $parent->method('getParent')->willReturn(null);
        return $parent;
    }

    public function testAttachmentFilterExcludesPartsWithinAttachedMultipart() : void
    {
        $callback = PartFilter::fromAttachmentFilter();

        $part = $this->createMock(IMessagePart::class);
        $part->method('getContentType')->willReturn('application/octet-stream');
        $part->method('getContentDisposition')->willReturn('attachment');
        $part->method('getParent')->willReturn($this->newAttachedMultipartParent());
        $this->assertFalse($callback($part));

        // the attached multipart may be further up the tree
        $middle = $this->createMock(IMimePart::class);
        $middle->method('isMultiPart')->willReturn(true);
        $middle->method('getContentDisposition')->willReturn(null);
        $middle->method('getParent')->willReturn($this->newAttachedMultipartParent());
        $part = $this->createMock(IMessagePart::class);
        $part->method('getContentType')->willReturn('application/octet-stream');
        $part->method('getContentDisposition')->willReturn('attachment');
        $part->method('getParent')->willReturn($middle);
        $this->assertFalse($callback($part));

        // inline multipart ancestors don't affect the part
        $middle = $this->createMock(IMimePart::class);
        $middle->method('isMultiPart')->willReturn(true);
        $middle->method('getContentDisposition')->willReturn('inline');
        $middle->method('getParent')->willReturn(null);
        $part = $this->createMock(IMessagePart::class);
        $part->method('getContentType')->willReturn('application/octet-stream');
        $part->method('getContentDisposition')->willReturn('attachment');
        $part->method('getParent')->willReturn($middle);
        $this->assertTrue($callback($part));
    }

    public function testHeaderValueFilterWithMessagePart() : void
    {
        $callback = PartFilter::fromHeaderValue('detective', 'peralta');
        $part = $this->createMock(IMessagePart::class);
        $this->assertFalse($callback($part));
    }

    public function testHeaderValueFilterWithSignaturePart() : void
    {
        $callback = PartFilter::fromHeaderValue('detective', 'peralta');
        $part = $this->createMock(IMimePart::class);
        $part->expects($this->once())->method('isSignaturePart')->willReturn(true);
        $part->expects($this->never())->method('getHeaderValue');
        $this->assertFalse($callback($part));
    }

    public function testHeaderValueFilterWithMimePart() : void
    {
        $callback = PartFilter::fromHeaderValue('detective', 'peralta');
        $part = $this->createMock(IMimePart::class);
        $part->method('isSignaturePart')->willReturnOnConsecutiveCalls(false, false, false, true, false, true);
        $part->method('getHeaderValue')->with('detective')->willReturnOnConsecutiveCalls(
            'PERAlta',
            'peralta',
            'HOLT!',
            'PERAlta',
            'peralta',
            'HOLT!'
        );
        $this->assertTrue($callback($part));
        $this->assertTrue($callback($part));
        $this->assertFalse($callback($part));

        $callback = PartFilter::fromHeaderValue('detective', 'peralta', false);
        $this->assertTrue($callback($part));
        $this->assertTrue($callback($part));
        $this->assertFalse($callback($part));
    }

    public function testContentTypeFilter() : void
    {
        $callback = PartFilter::fromContentType('text/plain');

        $part = $this->createMock(IMessagePart::class);
        $part->method('getContentType')->willReturnOnConsecutiveCalls('text/plain', 'text/html', 'text/plain', 'blah');
        $this->assertTrue($callback($part));
        $this->assertFalse($callback($part));
        $this->assertTrue($callback($part));
        $this->assertFalse($callback($part));
    }

    public function testInlineContentTypeFilter() : void
    {
        $callback = PartFilter::fromInlineContentType('text/plain');

        $part = $this->createMock(IMessagePart::class);
        $part->method('getContentType')->willReturnOnConsecutiveCalls('text/plain', 'text/html', 'text/plain', 'blah');
        $part->method('getContentDisposition')->willReturnOnConsecutiveCalls('inline', 'attachment', 'attoochment', 'attachment');
        $this->assertTrue($callback($part));
        $this->assertFalse($callback($part));
        $this->assertTrue($callback($part));
        $this->assertFalse($callback($part));
    }

    public function testInlineContentTypeFilterExcludesPartsWithinAttachedMultipart() : void
    {
        $callback = PartFilter::fromInlineContentType('text/html');

        $part = $this->createMock(IMessagePart::class);
        $part->method('getContentType')->willReturn('text/html');
        $part->method('getContentDisposition')->willReturn(null);
        $part->method('getParent')->willReturn($this->newAttachedMultipartParent());
        $this->assertFalse($callback($part));

        $inlineParent = $this->createMock(IMimePart::class);
        $inlineParent->method('isMultiPart')->willReturn(true);
        $inlineParent->method('getContentDisposition')->willReturn(null);
        $inlineParent->method('getParent')->willReturn(null);
        $part = $this->createMock(IMessagePart::class);
        $part->method('getContentType')->willReturn('text/html');
        $part->method('getContentDisposition')->willReturn(null);
        $part->method('getParent')->willReturn($inlineParent);
        $this->assertTrue($callback($part));
    }

    public function testDispositionFilter() : void
    {
        $callback = PartFilter::fromDisposition('needy');
        $part = $this->createMock(IMessagePart::class);
        $part->method('getContentDisposition')->willReturnOnConsecutiveCalls('inline', 'noodly', 'NEEDY', 'attachment', 'needy');
        $this->assertFalse($callback($part));
        $this->assertFalse($callback($part));
        $this->assertTrue($callback($part));
        $this->assertFalse($callback($part));
        $this->assertTrue($callback($part));
    }

    public function testDispositionFilterNoMultiOrSignedParts() : void
    {
        $callback = PartFilter::fromDisposition('needy');
        $part = $this->createMock(IMimePart::class);
        $part->method('getContentDisposition')->willReturnOnConsecutiveCalls('needy', 'needy', 'needy');
        $part->method('isMultiPart')->willReturnOnConsecutiveCalls(true, false, false);
        $part->method('isSignaturePart')->willReturnOnConsecutiveCalls(true, false);
        $this->assertFalse($callback($part));
        $this->assertFalse($callback($part));
        $this->assertTrue($callback($part));
    }

    public function testDispositionFilterWithMultiParts() : void
    {
        $callback = PartFilter::fromDisposition('greedy', true);
        $part = $this->createMock(IMimePart::class);
        $part->method('getContentDisposition')->willReturnOnConsecutiveCalls('greedy', 'greedy', 'greedy');
        $part->expects($this->never())->method('isMultiPart');
        $part->method('isSignaturePart')->willReturnOnConsecutiveCalls(false, true, false);
        $this->assertTrue($callback($part));
        $this->assertFalse($callback($part));
        $this->assertTrue($callback($part));
    }

    public function testDispositionFilterWithSignatureParts() : void
    {
        $callback = PartFilter::fromDisposition('seedy', false, true);
        $part = $this->createMock(IMimePart::class);
        $part->method('getContentDisposition')->willReturnOnConsecutiveCalls('seedy', 'seedy', 'seedy');
        $part->method('isMultiPart')->willReturnOnConsecutiveCalls(true, false, false);
        $part->expects($this->never())->method('isSignaturePart');
        $this->assertFalse($callback($part));
        $this->assertTrue($callback($part));
        $this->assertTrue($callback($part));
    }

    public function testDispositionFilterWithMultiAndSignatureParts() : void
    {
        $callback = PartFilter::fromDisposition('seedy', true, true);
        $part = $this->createMock(IMimePart::class);
        $part->method('getContentDisposition')->willReturnOnConsecutiveCalls('seedy', 'seedy', 'seedy');
        $part->expects($this->never())->method('isMultiPart');
        $part->expects($this->never())->method('isSignaturePart');
        $this->assertTrue($callback($part));
        $this->assertTrue($callback($part));
        $this->assertTrue($callback($part));
    }
}
