<?php
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\Message\PartFilter;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7;

/**
 * Description of MimePartTest
 *
 * @group MimePart
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\MimePart
 * @covers ZBateson\MailMimeParser\Message\Part\ParentHeaderPart
 * @covers ZBateson\MailMimeParser\Message\Part\ParentPart
 * @covers ZBateson\MailMimeParser\Message\Part\MessagePart
 * @author Zaahid Bateson
 */
class MimePartTest extends TestCase
{
    private $mockPartStreamFilterManager;
    private $mockPartFilterFactory;
    private $mockStreamFactory;

    protected function setUp()
    {
        $this->mockPartStreamFilterManager = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartFilterFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilterFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStreamFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getMockedParameterHeader($name, $value, $parameterValue = null)
    {
        $header = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'getRawValue', 'getName', 'getValueFor', 'hasParameter'])
            ->getMock();
        $header->method('getName')->willReturn($name);
        $header->method('getValue')->willReturn($value);
        $header->method('getRawValue')->willReturn($value);
        $header->method('getValueFor')->willReturn($parameterValue);
        $header->method('hasParameter')->willReturn(true);
        return $header;
    }

    protected function getMockedIdHeader($id)
    {
        $header = $this->getMockBuilder('ZBateson\MailMimeParser\Header\IdHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $header->method('getValue')->willReturn($id);
        return $header;
    }

    protected function getMockedPartBuilder()
    {
        $hc = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $pb->method('getHeaderContainer')
            ->willReturn($hc);
        return $pb;
    }

    protected function getMockedPartBuilderWithChildren()
    {
        $pb = $this->getMockedPartBuilder();
        $children = [
            $this->getMockedPartBuilder(),
            $this->getMockedPartBuilder(),
            $this->getMockedPartBuilder()
        ];

        $nested = $this->getMockedPartBuilder();
        $nested->method('createMessagePart')
            ->willReturn($this->newMimePart(
                $nested,
                Psr7\stream_for('nested')
            ));
        $children[0]->method('getChildren')
            ->willReturn([$nested]);

        foreach ($children as $key => $child) {
            $child->method('createMessagePart')
                ->willReturn($this->newMimePart(
                    $child,
                    Psr7\stream_for('child' . $key)
                ));
        }
        $pb->method('getChildren')
            ->willReturn($children);
        return $pb;
    }

    private function newMimePart($partBuilder, $stream = null, $contentStream = null)
    {
        return new MimePart(
            $this->mockPartStreamFilterManager,
            $this->mockStreamFactory,
            $this->mockPartFilterFactory,
            $partBuilder,
            $stream,
            $contentStream
        );
    }

    public function testInstance()
    {
        $part = $this->newMimePart($this->getMockedPartBuilder());
        $this->assertNotNull($part);
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Part\MimePart', $part);
        $this->assertTrue($part->isMime());
    }

    public function testCreateChildrenAndGetChildren()
    {
        $part = $this->newMimePart($this->getMockedPartBuilderWithChildren());
        $this->assertEquals(3, $part->getChildCount());
        $this->assertEquals('child0', stream_get_contents($part->getChild(0)->getResourceHandle()));
        $this->assertEquals('child1', stream_get_contents($part->getChild(1)->getResourceHandle()));
        $this->assertEquals('child2', stream_get_contents($part->getChild(2)->getResourceHandle()));
        $children = [
            $part->getChild(0),
            $part->getChild(1),
            $part->getChild(2)
        ];
        $this->assertEquals($children, $part->getChildParts());
    }

    public function testCreateChildrenAndGetParts()
    {
        $part = $this->newMimePart($this->getMockedPartBuilderWithChildren(), Psr7\stream_for('habibi'));
        $this->assertEquals(5, $part->getPartCount());

        $children = $part->getChildParts();
        $this->assertCount(3, $children);
        $nested = $children[0]->getChild(0);

        $this->assertSame($part, $part->getPart(0));
        $this->assertSame($children[0], $part->getPart(1));
        $this->assertSame($nested, $part->getPart(2));
        $this->assertSame($children[1], $part->getPart(3));
        $this->assertSame($children[2], $part->getPart(4));

        $this->assertEquals('habibi', stream_get_contents($part->getPart(0)->getResourceHandle()));
        $this->assertEquals('child0', stream_get_contents($part->getPart(1)->getResourceHandle()));
        $this->assertEquals('nested', stream_get_contents($part->getPart(2)->getResourceHandle()));
        $this->assertEquals('child1', stream_get_contents($part->getPart(3)->getResourceHandle()));
        $this->assertEquals('child2', stream_get_contents($part->getPart(4)->getResourceHandle()));

        $allParts = [ $part, $children[0], $nested, $children[1], $children[2]];
        $this->assertEquals($allParts, $part->getAllParts());
    }

    public function testSetRawHeaderAndRemoveHeader()
    {
        $firstHeader = $this->getMockedParameterHeader('First-Header', 'Value');
        $secondHeader = $this->getMockedParameterHeader('Second-Header', 'Second Value');

        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $ms = Psr7\stream_for('message');
        $part = $this->newMimePart($pb, $ms);

        // make sure markAsChanged is called
        $this->assertSame($ms, $part->getStream());
        $this->mockStreamFactory
            ->expects($this->once())
            ->method('newMessagePartStream')
            ->with($part)
            ->willReturn('Much success');

        $hc->method('get')
            ->willReturnMap([
                [ $firstHeader->getName(), 0, $firstHeader ],
                [ $secondHeader->getName(), 0, $secondHeader ]
            ]);
        $hc->method('getHeaders')
            ->willReturn([
                [ $firstHeader->getName(), $firstHeader-> getValue() ],
                [ $secondHeader->getName(), $secondHeader-> getValue() ]
            ]);

        $hc->expects($this->once())
            ->method('set')
            ->with($firstHeader->getName(), $firstHeader->getValue(), 1);
        $part->setRawHeader($firstHeader->getName(), $firstHeader->getValue(), 1);
        $this->assertEquals('Much success', $part->getStream());

        $hc->expects($this->once())
            ->method('add')
            ->with($secondHeader->getName(), $secondHeader->getValue());
        $part->addRawHeader($secondHeader->getName(), $secondHeader->getValue());
        $this->assertSame($firstHeader, $part->getHeader($firstHeader->getName()));
        $this->assertSame($secondHeader, $part->getHeader($secondHeader->getName()));
        $this->assertEquals($firstHeader->getValue(), $part->getHeaderValue($firstHeader->getName()));
        $this->assertEquals($secondHeader->getValue(), $part->getHeaderValue($secondHeader->getName()));

        $this->assertCount(2, $part->getRawHeaders());
        $this->assertEquals([[ 'First-Header', $firstHeader->getRawValue() ], [ 'Second-Header', $secondHeader->getRawValue() ]], $part->getRawHeaders());

        $hc->expects($this->once())
            ->method('removeAll')
            ->with('FIRST-header');
        $part->removeHeader('FIRST-header');

        $hc->expects($this->once())
            ->method('remove')
            ->with('First-Header', 0);
        $part->removeSingleHeader('First-Header');
    }

    public function testGetHeaderParameter()
    {
        $header = $this->getMockedParameterHeader('First-Header', 'Value', 'param-value');

        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->once())
            ->method('get')
            ->with('first-header', 0)
            ->willReturn($header);

        $part = $this->newMimePart($pb);
        $this->assertEquals('param-value', $part->getHeaderParameter('first-header', 'param'));
    }

    public function testGetUnsetHeaderParameter()
    {
        $part = $this->newMimePart($this->getMockedPartBuilder());
        $this->assertNull($part->getHeaderParameter('Nothing', 'Non-Existent'));
        $this->assertEquals('Default', $part->getHeaderParameter('Nothing', 'Non-Existent', 'Default'));
    }

    public function testOnChangeParents()
    {
        $ms = Psr7\stream_for('parent');
        $part = $this->newMimePart($this->getMockedPartBuilderWithChildren(), $ms);

        $children = $part->getChildParts();
        $nested = $children[0]->getChild(0);

        $msChildren = [ $children[0]->getStream(), $children[1]->getStream(), $children[2]->getStream() ];
        $ns = $nested->getStream();

        $msFirst = Psr7\stream_for('first');
        $first = $this->newMimePart($this->getMockedPartBuilder(), $msFirst);

        $this->assertSame($ms, $part->getStream());

        $this->mockStreamFactory
            ->expects($this->exactly(3))
            ->method('newMessagePartStream')
            ->willReturnMap([
                [ $nested, 'Nested success' ],
                [ $children[0], 'Child parent success' ],
                [ $part, 'Parent success' ],
            ]);

        $nested->addChild($first);

        $this->assertSame($msFirst, $first->getStream());
        $this->assertEquals('Nested success', $nested->getStream());
        $this->assertEquals('Child parent success', $children[0]->getStream());
        $this->assertEquals('Parent success', $part->getStream());

        $this->assertSame($msChildren[1], $children[1]->getStream());
        $this->assertSame($msChildren[2], $children[2]->getStream());
    }

    public function testAddRemoveAndGetPart()
    {
        $first = $this->newMimePart($this->getMockedPartBuilder());
        $second = $this->newMimePart($this->getMockedPartBuilder());
        $third = $this->newMimePart($this->getMockedPartBuilder());
        $parent = $this->newMimePart($this->getMockedPartBuilder());

        $parent->addChild($first);
        $parent->addChild($second);
        $second->addChild($third);

        $this->assertSame($parent, $first->getParent());
        $this->assertSame($parent, $second->getParent());
        $this->assertSame($second, $third->getParent());

        $this->assertEquals(4, $parent->getPartCount());
        $this->assertSame($parent, $parent->getPart(0));
        $this->assertSame($first, $parent->getPart(1));
        $this->assertSame($second, $parent->getPart(2));
        $this->assertSame($third, $parent->getPart(3));
        $this->assertSame($third, $second->getPart(1));
        $this->assertNull($parent->getPart(4));

        $this->assertEquals(
            [$parent, $first, $second, $third],
            $parent->getAllParts()
        );

        $this->assertEquals($parent->removePart($first), 0);
        $this->assertEquals(3, $parent->getPartCount());
        $this->assertSame($parent, $parent->getPart(0));
        $this->assertSame($second, $parent->getPart(1));
        $this->assertSame($third, $parent->getPart(2));
        $this->assertNull($parent->getPart(3));

        $second->removeAllParts();
        $this->assertEquals(2, $parent->getPartCount());
        $this->assertSame($parent, $parent->getPart(0));
        $this->assertSame($second, $parent->getPart(1));
        $this->assertNull($parent->getPart(2));

        $this->assertEquals(
            [ $parent, $second ],
            $parent->getAllParts()
        );
    }

    public function testAddRemoveAndGetChildParts()
    {
        $first = $this->newMimePart($this->getMockedPartBuilder());
        $second = $this->newMimePart($this->getMockedPartBuilder());
        $third = $this->newMimePart($this->getMockedPartBuilder());
        $parent = $this->newMimePart($this->getMockedPartBuilder());

        $parent->addChild($first);
        $parent->addChild($second);
        $second->addChild($third);

        $this->assertSame($parent, $first->getParent());
        $this->assertSame($parent, $second->getParent());
        $this->assertSame($second, $third->getParent());

        $this->assertEquals(2, $parent->getChildCount());
        $this->assertSame($first, $parent->getChild(0));
        $this->assertSame($second, $parent->getChild(1));
        $this->assertNull($parent->getChild(2));
        $this->assertSame($third, $second->getChild(0));

        $this->assertEquals(
            [$first, $second],
            $parent->getChildParts()
        );

        $this->assertEquals($third->removePart($first), 0);
        $this->assertNull($parent->removePart($first));

        $this->assertEquals(1, $parent->getChildCount());
        $this->assertSame($second, $parent->getChild(0));
        $this->assertNull($parent->getChild(1));
        $this->assertSame($third, $second->getChild(0));

        $second->removeAllParts();
        $this->assertEquals(1, $parent->getChildCount());
        $this->assertSame($second, $parent->getChild(0));
        $this->assertEquals(0, $second->getChildCount());
        $this->assertNull($second->getChild(0));

        $this->assertEquals(
            [$second],
            $parent->getChildParts()
        );
    }

    public function testGetFilteredParts()
    {
        $part = $this->newMimePart($this->getMockedPartBuilderWithChildren());
        $parts = $part->getAllParts();
        $filterMock = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->setMethods(['filter'])
            ->getMock();
        $filterMock->expects($this->exactly(5))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(false, true, false, true, false);

        $returned = $part->getAllParts($filterMock);
        $this->assertCount(2, $returned);
        $this->assertEquals([$parts[1], $parts[3]], $returned);
    }

    public function testGetFilteredChildParts()
    {
        $part = $this->newMimePart($this->getMockedPartBuilderWithChildren());
        $parts = $part->getAllParts();

        $filterMock = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->setMethods(['filter'])
            ->getMock();
        $filterMock->expects($this->exactly(3))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(false, true, false);

        $returned = $part->getChildParts($filterMock);
        $this->assertCount(1, $returned);
        $this->assertEquals([$parts[3]], $returned);
    }

    public function testGetUnsetHeader()
    {
        $part = $this->newMimePart($this->getMockedPartBuilder());
        $this->assertNull($part->getHeader('blah'));
        $this->assertEquals('upside-down', $part->getHeaderValue('blah', 'upside-down'));
        $this->assertEquals('demigorgon', $part->getHeaderParameter('blah', 'blah', 'demigorgon'));
    }

    public function testGetContentDisposition()
    {
        $header = $this->getMockedParameterHeader('meen?', 'habibi');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->exactly(2))
            ->method('get')
            ->with('Content-Disposition', 0)
            ->willReturn($header);

        $part = $this->newMimePart($pb, Psr7\stream_for('habibi'));
        $this->assertSame($header, $part->getHeader('Content-Disposition'));
        $this->assertEquals('habibi', $part->getContentDisposition());
    }

    public function testGetContentTransferEncoding()
    {
        $header = $this->getMockedParameterHeader('meen?', 'x-uue');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->exactly(2))
            ->method('get')
            ->with('Content-Transfer-Encoding', 0)
            ->willReturn($header);

        $part = $this->newMimePart($pb, Psr7\stream_for('habibi'));
        $this->assertSame($header, $part->getHeader('Content-Transfer-Encoding'));
        $this->assertEquals('x-uuencode', $part->getContentTransferEncoding());
    }

    public function testGetCharset()
    {
        $header = $this->getMockedParameterHeader('content-type', 'text/plain', 'blah');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->once())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);

        $part = $this->newMimePart($pb);
        $this->assertEquals('BLAH', $part->getCharset());
    }

    public function testGetFilename()
    {
        $header = $this->getMockedParameterHeader('content-type', 'text/plain', 'blooh');
        $header2 = $this->getMockedParameterHeader('content-disposition', 'attachment', 'blah');

        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [ 'Content-Type', 0 ],
                [ 'Content-Disposition', 0 ]
            )
            ->willReturnOnConsecutiveCalls($header, $header2);

        $part = $this->newMimePart($pb);
        $this->assertEquals('blah', $part->getFilename());
    }

    public function testGetDefaultCharsetForTextPlainAndTextHtml()
    {
        $headerText = $this->getMockedParameterHeader('content-type', 'text/plain');
        $headerHtml = $this->getMockedParameterHeader('content-type', 'text/html');

        $pbText = $this->getMockedPartBuilder();
        $hc = $pbText->getHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($headerText);

        $pbHtml = $this->getMockedPartBuilder();
        $hc2 = $pbHtml->getHeaderContainer();
        $hc2->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($headerHtml);

        $partText = $this->newMimePart($pbText);
        $partHtml = $this->newMimePart($pbHtml);

        $this->assertEquals('ISO-8859-1', $partText->getCharset());
        $this->assertEquals('ISO-8859-1', $partHtml->getCharset());
    }

    public function testGetNullCharsetForNonTextPlainOrHtmlPart()
    {
        $header = $this->getMockedParameterHeader('content-type', 'text/rtf');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);
        $part = $this->newMimePart($pb);
        $this->assertNull($part->getCharset());
    }

    public function testUsesTransferEncodingAndCharsetForStreamFilter()
    {
        $headerType = $this->getMockedParameterHeader('Content-Type', 'text/plain', 'wingding');
        $headerEnc = $this->getMockedParameterHeader('Content-Transfer-Encoding', 'klingon');

        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->method('get')
            ->willReturnMap([
                [ 'Content-Type', 0, $headerType ],
                [ 'Content-Transfer-Encoding', 0, $headerEnc ]
            ]);

        $manager = $this->mockPartStreamFilterManager;
        $manager->expects($this->once())
            ->method('getContentStream')
            ->with('klingon', 'WINGDING', 'UTF-8')
            ->willReturn(Psr7\stream_for('totally not null'));

        $part = $this->newMimePart($pb, Psr7\stream_for('habibi'), Psr7\stream_for('blah'));
        $this->assertEquals('WINGDING', $part->getCharset());
        $this->assertEquals('klingon', $part->getContentTransferEncoding());
        $this->assertNotNull($part->getContentResourceHandle());
    }

    public function testIsTextIsMultiPartForNonTextNonMultipart()
    {
        $header = $this->getMockedParameterHeader('Content-Type', 'stuff/blooh');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);
        $part = $this->newMimePart($pb);
        $this->assertFalse($part->isMultiPart());
        $this->assertFalse($part->isTextPart());
    }

    public function testIsTextForTextPlain()
    {
        $header = $this->getMockedParameterHeader('Content-Type', 'text/plain');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);
        $part = $this->newMimePart($pb);
        $this->assertFalse($part->isMultiPart());
        $this->assertTrue($part->isTextPart());
    }

    public function testIsTextForTextHtml()
    {
        $header = $this->getMockedParameterHeader('Content-Type', 'text/html');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);
        $part = $this->newMimePart($pb);
        $this->assertFalse($part->isMultiPart());
        $this->assertTrue($part->isTextPart());
    }

    public function testIsTextForTextMimeTypeWithCharset()
    {
        $header = $this->getMockedParameterHeader('Content-Type', 'text/blah', 'utf-8');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);
        $part = $this->newMimePart($pb);
        $this->assertFalse($part->isMultiPart());
        $this->assertTrue($part->isTextPart());
    }

    public function testIsTextForTextMimeTypeWithoutCharset()
    {
        $header = $this->getMockedParameterHeader('Content-Type', 'text/blah');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);
        $part = $this->newMimePart($pb);
        $this->assertFalse($part->isMultiPart());
        $this->assertFalse($part->isTextPart());
    }

    public function testIsMultipartForMultipartRelated()
    {
        $header = $this->getMockedParameterHeader('Content-Type', 'multipart/related');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);
        $part = $this->newMimePart($pb);
        $this->assertTrue($part->isMultiPart());
        $this->assertFalse($part->isTextPart());
    }

    public function testIsMultipartForMultipartAnything()
    {
        $header = $this->getMockedParameterHeader('Content-Type', 'multipart/anything');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->expects($this->atLeastOnce())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);
        $part = $this->newMimePart($pb);
        $this->assertTrue($part->isMultiPart());
        $this->assertFalse($part->isTextPart());
    }

    public function testGetContentId()
    {
        $header = $this->getMockedIdHeader('1337');
        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->method('get')
            ->willReturnOnConsecutiveCalls($header, null);

        $part = $this->newMimePart($pb);
        $this->assertEquals('1337', $part->getContentId());
        $this->assertNull($part->getContentId());
    }


    public function testGetAllPartsByMimeType()
    {
        $pf = $this->mockPartFilterFactory;
        $filter = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $filter->expects($this->exactly(5))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(true, true, false, false, false);

        $pf->expects($this->once())
            ->method('newFilterFromContentType')
            ->with('awww geez')
            ->willReturn($filter);

        $part = $this->newMimePart($this->getMockedPartBuilderWithChildren());
        $parts = $part->getAllPartsByMimeType('awww geez');
        $this->assertCount(2, $parts);
    }

    public function testGetPartByMimeType()
    {
        $pf = $this->mockPartFilterFactory;
        $filter = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $filter->expects($this->exactly(10))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(
                true, false, false, true, false,
                true, false, false, true, false
            );

        $pf->expects($this->exactly(2))
            ->method('newFilterFromContentType')
            ->with('awww geez')
            ->willReturn($filter);

        $part = $this->newMimePart($this->getMockedPartBuilderWithChildren());
        $this->assertSame($part, $part->getPartByMimeType('awww geez'));
        $this->assertSame($part->getPart(3), $part->getPartByMimeType('awww geez', 1));
    }

    public function testGetCountOfPartsByMimeType()
    {
        $pf = $this->mockPartFilterFactory;
        $filter = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $filter->expects($this->exactly(5))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(true, true, false, false, true);

        $pf->expects($this->once())
            ->method('newFilterFromContentType')
            ->with('awww geez, Rick')
            ->willReturn($filter);

        $part = $this->newMimePart($this->getMockedPartBuilderWithChildren());
        $this->assertEquals(3, $part->getCountOfPartsByMimeType('awww geez, Rick'));
    }

    public function testGetPartByContentId()
    {
        $pf = $this->mockPartFilterFactory;
        $filter = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $filter->expects($this->exactly(15))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(
                true, false, false, true, false,
                true, false, false, true, false,
                true, false, false, true, false
            );

        $pf->expects($this->exactly(3))
            ->method('newFilterFromArray')
            ->with([
                'headers' => [
                    PartFilter::FILTER_INCLUDE => [
                        'Content-ID' => 'the-id'
                    ]
                ]
            ])
            ->willReturn($filter);

        $part = $this->newMimePart($this->getMockedPartBuilderWithChildren());
        $this->assertSame($part, $part->getPartByContentId('  <the-id>  '));
        $this->assertSame($part, $part->getPartByContentId('<the-id>'));
        $this->assertSame($part, $part->getPartByContentId('the-id'));
    }
}
