<?php
namespace ZBateson\MailMimeParser\Message;

use LegacyPHPUnit\TestCase;

/**
 * PartFilterTest
 *
 * @group PartFilter
 * @group Message
 * @covers ZBateson\MailMimeParser\MessageFilter
 * @author Zaahid Bateson
 */
class PartFilterTest extends TestCase
{
    private $parts = [];

    protected function getMockedPartWithContentType($mimeType, $disposition = null, $isText = false)
    {
        $part = $this->getMockBuilder('ZBateson\MailMimeParser\Message\MimePart')
            ->disableOriginalConstructor()
            ->setMethods([
                '__destruct',
                'setRawHeader',
                'getHeader',
                'getHeaderValue',
                'getHeaderParameter',
                'getContentResourceHandle',
                'getParent',
                'getContentType',
                'getContentDisposition',
                'isTextPart',
            ])
            ->getMock();
        $part->method('getContentType')->willReturn($mimeType);
        $part->method('getContentDisposition')->willReturn($disposition);
        $part->method('isTextPart')->willReturn($isText);
        return $part;
    }

    protected function getMockedSignedPart()
    {
        $parent = $this->getMockedPartWithContentType('multipart/signed');
        $parent->method('getHeaderParameter')->willReturn('signed/part');
        $part = $this->getMockedPartWithContentType('signed/part');
        $part->method('getParent')->willReturn($parent);
        return $part;
    }

    protected function legacySetUp()
    {
        $signedPart = $this->getMockedSignedPart();
        $signedPartParent = $signedPart->getParent();
        $this->parts = [
            $this->getMockedPartWithContentType('text/html', null, true),
            $this->getMockedPartWithContentType('multipart/alternative', 'inline'),
            $this->getMockedPartWithContentType('text/html', 'inline', true),
            $this->getMockedPartWithContentType('text/plain', 'attachment', true),
            $this->getMockedPartWithContentType('text/html', 'attachment', true),
            $this->getMockedPartWithContentType('multipart/relative'),
            $signedPartParent,
            $signedPart
        ];
    }

    public function testFromContentType()
    {

        $filter = PartFilter::fromContentType('text/html');
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $this->assertArrayHasKey(PartFilter::FILTER_INCLUDE, $filter->headers);
        $this->assertArrayHasKey('Content-Type', $filter->headers[PartFilter::FILTER_INCLUDE]);
        $this->assertEquals('text/html', $filter->headers[PartFilter::FILTER_INCLUDE]['Content-Type']);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(3, $filtered);
        $this->assertSame($this->parts[0], $filtered[0]);
        $this->assertSame($this->parts[2], $filtered[1]);
        $this->assertSame($this->parts[4], $filtered[2]);
    }

    public function testFromInlineContentType()
    {
        $filter = PartFilter::fromInlineContentType('text/html');
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $this->assertArrayHasKey(PartFilter::FILTER_INCLUDE, $filter->headers);
        $this->assertArrayHasKey(PartFilter::FILTER_EXCLUDE, $filter->headers);
        $this->assertArrayHasKey('Content-Type', $filter->headers[PartFilter::FILTER_INCLUDE]);
        $this->assertArrayHasKey('Content-Disposition', $filter->headers[PartFilter::FILTER_EXCLUDE]);
        $this->assertEquals('text/html', $filter->headers[PartFilter::FILTER_INCLUDE]['Content-Type']);
        $this->assertEquals('attachment', $filter->headers[PartFilter::FILTER_EXCLUDE]['Content-Disposition']);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(2, $filtered);
        $this->assertSame($this->parts[0], $filtered[0]);
        $this->assertSame($this->parts[2], $filtered[1]);
    }

    public function testFromNonExistentContentType()
    {

        $filter = PartFilter::fromContentType('doesnot/exist');
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $this->assertArrayHasKey(PartFilter::FILTER_INCLUDE, $filter->headers);
        $this->assertArrayHasKey('Content-Type', $filter->headers[PartFilter::FILTER_INCLUDE]);
        $this->assertEquals('doesnot/exist', $filter->headers[PartFilter::FILTER_INCLUDE]['Content-Type']);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertEmpty($filtered);
    }

    public function testFromDispositionAttachment()
    {
        $filter = PartFilter::fromDisposition('attachment');
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);

        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(2, $filtered);
        $this->assertSame($this->parts[3], $filtered[0]);
        $this->assertSame($this->parts[4], $filtered[1]);
    }

    public function testFromDispositionInline()
    {
        $filter = PartFilter::fromDisposition('inline');
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(2, $filtered);
        $this->assertSame($this->parts[1], $filtered[0]);
        $this->assertSame($this->parts[2], $filtered[1]);
    }

    public function testFromDispositionInlineExcludeMultiPart()
    {
        $filter = PartFilter::fromDisposition('inline', PartFilter::FILTER_EXCLUDE);
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(1, $filtered);
        $this->assertSame($this->parts[2], $filtered[0]);
    }

    public function testFromDispositionInlineIncludeMultiPart()
    {
        $filter = PartFilter::fromDisposition('inline', PartFilter::FILTER_INCLUDE);
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_INCLUDE, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(1, $filtered);
        $this->assertSame($this->parts[1], $filtered[0]);
    }

    public function testForNonExistentDisposition()
    {
        $filter = PartFilter::fromDisposition('unreal');
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertEmpty($filtered);
    }

    public function testMultiPartFilterInclude()
    {
        $filter = new PartFilter([
            'multipart' => PartFilter::FILTER_INCLUDE,
        ]);
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_INCLUDE, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);

        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(3, $filtered);
        $this->assertSame($this->parts[1], $filtered[0]);
        $this->assertSame($this->parts[5], $filtered[1]);
        $this->assertSame($this->parts[6], $filtered[2]);
    }

    public function testMultiPartFilterExcludeWithHeaderExcludeFilter()
    {
        $filter = new PartFilter([
            'multipart' => PartFilter::FILTER_EXCLUDE,
            'headers' => [
                PartFilter::FILTER_EXCLUDE => [
                    'Content-Type' => 'text/html'
                ]
            ]
        ]);
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(1, $filtered);
        $this->assertSame($this->parts[3], $filtered[0]);
    }

    public function testMultiPartFilterIncludeWithHeaderExcludeFilter()
    {
        $filter = new PartFilter([
            'multipart' => PartFilter::FILTER_INCLUDE,
            'headers' => [
                PartFilter::FILTER_EXCLUDE => [
                    'Content-Type' => 'multipart/alternative'
                ]
            ]
        ]);
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_INCLUDE, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(2, $filtered);
        $this->assertSame($this->parts[5], $filtered[0]);
        $this->assertSame($this->parts[6], $filtered[1]);
    }

    public function testTextPartFilterInclude()
    {
        $filter = new PartFilter([
            'textpart' => PartFilter::FILTER_INCLUDE,
        ]);
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_INCLUDE, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);

        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(4, $filtered);
        $this->assertSame($this->parts[0], $filtered[0]);
        $this->assertSame($this->parts[2], $filtered[1]);
        $this->assertSame($this->parts[3], $filtered[2]);
        $this->assertSame($this->parts[4], $filtered[3]);
    }

    public function testTextPartFilterExcludeWithHeaderExcludeFilter()
    {
        $filter = new PartFilter([
            'textpart' => PartFilter::FILTER_EXCLUDE,
            'headers' => [
                PartFilter::FILTER_EXCLUDE => [
                    'Content-Type' => 'multipart/relative'
                ]
            ]
        ]);
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(2, $filtered);
        $this->assertSame($this->parts[1], $filtered[0]);
        $this->assertSame($this->parts[6], $filtered[1]);
    }

    public function testTextPartFilterIncludeWithHeaderExcludeFilter()
    {
        $filter = new PartFilter([
            'textpart' => PartFilter::FILTER_INCLUDE,
            'headers' => [
                PartFilter::FILTER_EXCLUDE => [
                    'Content-Type' => 'text/html'
                ]
            ]
        ]);
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_INCLUDE, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $filter->signedpart);
        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(1, $filtered);
        $this->assertSame($this->parts[3], $filtered[0]);
    }

    public function testSignedPartFilterInclude()
    {
        $filter = new PartFilter([
            'signedpart' => PartFilter::FILTER_INCLUDE,
        ]);
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_INCLUDE, $filter->signedpart);

        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(1, $filtered);
        $this->assertSame($this->parts[7], $filtered[0]);
    }

    public function testSignedPartFilterOffWithDispositionExclude()
    {
        $filter = new PartFilter([
            'signedpart' => PartFilter::FILTER_OFF,
            'headers' => [
                PartFilter::FILTER_EXCLUDE => [
                    'Content-Disposition' => 'inline',
                ]
            ]
        ]);
        $this->assertNotNull($filter);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\MessageFilter', $filter);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->textpart);
        $this->assertEquals(PartFilter::FILTER_OFF, $filter->signedpart);

        $filtered = array_values(array_filter($this->parts, [ $filter, 'filter' ]));
        $this->assertCount(6, $filtered);
        $this->assertSame($this->parts[0], $filtered[0]);
        $this->assertSame($this->parts[3], $filtered[1]);
        $this->assertSame($this->parts[4], $filtered[2]);
        $this->assertSame($this->parts[5], $filtered[3]);
        $this->assertSame($this->parts[6], $filtered[4]);
        $this->assertSame($this->parts[7], $filtered[5]);
    }
}
