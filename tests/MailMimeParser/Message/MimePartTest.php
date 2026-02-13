<?php

namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;
use Traversable;
use ZBateson\MailMimeParser\ConsecutiveCallsTrait;
use ZBateson\MailMimeParser\Header\IHeader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of MimePartTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(MimePart::class)]
#[CoversClass(MultiPart::class)]
#[CoversClass(MessagePart::class)]
#[Group('MimePart')]
#[Group('MessagePart')]
class MimePartTest extends TestCase
{
    use ConsecutiveCallsTrait;

    // @phpstan-ignore-next-line
    private $mockPartStreamContainer;

    // @phpstan-ignore-next-line
    private $mockHeaderContainer;

    // @phpstan-ignore-next-line
    private $mockPartChildrenContainer;

    protected function setUp() : void
    {
        $this->mockPartStreamContainer = $this->getMockBuilder(PartStreamContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockHeaderContainer = $this->getMockBuilder(PartHeaderContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartChildrenContainer = $this->getMockBuilder(PartChildrenContainer::class)
            ->getMock();
    }

    private function getMimePart($childrenContainer = null, $headerContainer = null, $streamContainer = null, $parent = null) : MimePart
    {
        if ($childrenContainer === null) {
            $childrenContainer = $this->mockPartChildrenContainer;
        }
        if ($headerContainer === null) {
            $headerContainer = $this->mockHeaderContainer;
        }
        if ($streamContainer === null) {
            $streamContainer = $this->mockPartStreamContainer;
        }
        return new MimePart($parent, \mmpGetTestLogger(), $streamContainer, $headerContainer, $childrenContainer);
    }

    protected function getMockedParameterHeader($name, $value, $parameterValue = null) : \ZBateson\MailMimeParser\Header\ParameterHeader
    {
        $header = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\ParameterHeader::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue', 'getRawValue', 'getName', 'getValueFor', 'hasParameter'])
            ->getMock();
        $header->method('getName')->willReturn($name);
        $header->method('getValue')->willReturn($value);
        $header->method('getRawValue')->willReturn($value);
        $header->method('getValueFor')->willReturn($parameterValue);
        $header->method('hasParameter')->willReturn(($parameterValue !== null));
        return $header;
    }

    protected function getMockedIdHeader($id) : \ZBateson\MailMimeParser\Header\IdHeader
    {
        $header = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\IdHeader::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMock();
        $header->method('getValue')->willReturn($id);
        return $header;
    }

    public function testGetFileName() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer->expects($this->atLeastOnce())
            ->method('get')
            ->with(...$this->consecutive(
                [$this->equalTo('Content-Type'), 0],
                [$this->equalTo('Content-Disposition'), 0],
                [$this->equalTo('Content-Type'), 0],
                [$this->equalTo('Content-Disposition'), 0],
                [$this->equalTo('Content-Type'), 0],
                [$this->equalTo('Content-Disposition'), 0]
            ))
            ->willReturnOnConsecutiveCalls(
                $this->getMockedParameterHeader('Content-Type', 'blah-blooh', null),
                $this->getMockedParameterHeader('Content-Disposition', 'attachment', 'bin-bashy.jpg'),
                $this->getMockedParameterHeader('Content-Type', 'blah-blooh', '/bin/bashy'),
                null,
                $this->getMockedParameterHeader('Content-Type', 'blah-blooh', null),
                null
            );
        $this->assertEquals('bin-bashy.jpg', $part->getFilename());
        $this->assertEquals('/bin/bashy', $part->getFilename());
        $this->assertNull($part->getFilename());
    }

    public function testIsMimeDefaultContentTypeAndCharset() : void
    {
        $part = $this->getMimePart();
        $this->assertTrue($part->isMime());
        $this->assertTrue($part->isTextPart());
        $this->assertEquals('text/plain', $part->getContentType());
        $this->assertEquals('ISO-8859-1', $part->getCharset());
    }

    public function testGetContentType() : void
    {
        $part = $this->getMimePart();
        $header = $this->getMockedParameterHeader('content-type', 'MEEP/MOOP');
        $this->mockHeaderContainer->expects($this->once())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);
        $this->assertEquals('meep/moop', $part->getContentType());
    }

    public function testGetCharset() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturnOnConsecutiveCalls(
                $this->getMockedParameterHeader('content-type', 'text/plain', 'blah'),
                $this->getMockedParameterHeader('content-type', 'arooga', 'binary'),
                $this->getMockedParameterHeader('content-type', 'arooga', 'binary')
            );
        $this->assertEquals('BLAH', $part->getCharset());
        $this->assertNull($part->getCharset());
    }

    public function testDefaultCharset() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturnOnConsecutiveCalls(
                $this->getMockedParameterHeader('content-type', 'MEEP/MOOP'),
                $this->getMockedParameterHeader('content-type', 'MEEP/MOOP'),
                $this->getMockedParameterHeader('content-type', 'text/plain'),
                $this->getMockedParameterHeader('content-type', 'text/plain'),
                $this->getMockedParameterHeader('content-type', 'text/html', 'binary'),
                $this->getMockedParameterHeader('content-type', 'text/html', 'binary'),
                $this->getMockedParameterHeader('content-type', 'multipart/extra'),
                $this->getMockedParameterHeader('content-type', 'multipart/extra')
            );
        $this->assertNull($part->getCharset());
        $this->assertEquals('ISO-8859-1', $part->getCharset());
        $this->assertEquals('ISO-8859-1', $part->getCharset());
        $this->assertNull($part->getCharset());
    }

    public function testGetContentDisposition() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->method('get')
            ->with('Content-Disposition', 0)
            ->willReturn($this->getMockedParameterHeader('meen?', 'habibi'));
        $this->assertEquals('inline', $part->getContentDisposition());
    }

    public function testGetContentTransferEncoding() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->method('get')
            ->with('Content-Transfer-Encoding', 0)
            ->willReturnOnConsecutiveCalls(
                $this->getMockedParameterHeader('meen?', 'habibi'),
                $this->getMockedParameterHeader('meen?', 'x-uue'),
                $this->getMockedParameterHeader('meen?', 'uue'),
                $this->getMockedParameterHeader('meen?', 'uuencode'),
                $this->getMockedParameterHeader('meen?', 'quoted-printable'),
                $this->getMockedParameterHeader('meen?', 'base64')
            );

        $this->assertEquals('habibi', $part->getContentTransferEncoding());
        $this->assertEquals('x-uuencode', $part->getContentTransferEncoding());
        $this->assertEquals('x-uuencode', $part->getContentTransferEncoding());
        $this->assertEquals('x-uuencode', $part->getContentTransferEncoding());
        $this->assertEquals('quoted-printable', $part->getContentTransferEncoding());
        $this->assertEquals('base64', $part->getContentTransferEncoding());
    }

    public function testGetContentId() : void
    {
        $part = $this->getMimePart();
        $header = $this->getMockedIdHeader('1337');
        $this->mockHeaderContainer
            ->method('get')
            ->willReturnOnConsecutiveCalls($header, null);
        $this->assertEquals('1337', $part->getContentId());
        $this->assertNull($part->getContentId());
    }

    public function testIsSignaturePart() : void
    {
        $part = $this->getMimePart();
        $this->assertFalse($part->isSignaturePart());

        $parentMimePart = $this->getMimePart();
        $parentMimePart->addChild($part);
        $this->assertFalse($part->isSignaturePart());

        $message = $this->getMockBuilder(\ZBateson\MailMimeParser\Message::class)
            ->setConstructorArgs([
                \mmpGetTestLogger(),
                $this->getMockBuilder(PartStreamContainer::class)->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder(PartHeaderContainer::class)->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder(PartChildrenContainer::class)->getMock(),
                $this->getMockBuilder(Helper\MultipartHelper::class)->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder(Helper\PrivacyHelper::class)->disableOriginalConstructor()->getMock()
            ])
            ->onlyMethods(['getSignaturePart'])
            ->getMock();
        $message->expects($this->once())->method('getSignaturePart')->willReturn($part);
        $message->addChild($part);

        $this->assertTrue($part->isSignaturePart());
    }

    public function testGetHeader() : void
    {
        $part = $this->getMimePart();

        $h1 = $this->createMock(IHeader::class);
        $h2 = $this->createMock(IHeader::class);

        $this->mockHeaderContainer
            ->expects($this->exactly(2))
            ->method('get')
            ->with(...$this->consecutive(
                ['foist', 0],
                ['sekint', 1]
            ))->willReturnOnConsecutiveCalls($h1, $h2);
        $this->assertEquals($h1, $part->getHeader('foist'));
        $this->assertEquals($h2, $part->getHeader('sekint', 1));
    }

    public function testGetHeaderAs() : void
    {
        $part = $this->getMimePart();
        $oRet = $this->createMock(\ZBateson\MailMimeParser\Header\IHeader::class);
        $oRet2 = $this->createMock(\ZBateson\MailMimeParser\Header\IHeader::class);
        $this->mockHeaderContainer
            ->expects($this->exactly(2))
            ->method('getAs')
            ->with(...$this->consecutive(
                ['foist', 'IHeaderClass', 0],
                ['sekint', 'IHeaderClass', 1]
            ))->willReturnOnConsecutiveCalls($oRet, $oRet2);
        $this->assertEquals($oRet, $part->getHeaderAs('foist', 'IHeaderClass'));
        $this->assertEquals($oRet2, $part->getHeaderAs('sekint', 'IHeaderClass', 1));
    }

    public function testGetAllHeaders() : void
    {
        $part = $this->getMimePart();
        $headers = [
            $this->createMock(IHeader::class),
            $this->createMock(IHeader::class)
        ];
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('getHeaderObjects')
            ->willReturn($headers);
        $this->assertEquals($headers, $part->getAllHeaders());
    }

    public function testGetAllHeadersByName() : void
    {
        $part = $this->getMimePart();
        $headers = [
            $this->createMock(IHeader::class),
            $this->createMock(IHeader::class)
        ];
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('getAll')
            ->with('dahoida')
            ->willReturn($headers);
        $this->assertEquals($headers, $part->getAllHeadersByName('dahoida'));
    }

    public function testGetRawHeaders() : void
    {
        $part = $this->getMimePart();
        $headers = [
            $this->createMock(IHeader::class),
            $this->createMock(IHeader::class)
        ];
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);
        $this->assertEquals($headers, $part->getRawHeaders());
    }

    public function testGetRawHeadersIterator() : void
    {
        $part = $this->getMimePart();
        $iter = $this->createMock(Traversable::class);
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn($iter);
        $this->assertEquals($iter, $part->getRawHeaderIterator());
    }

    public function testGetHeaderValue() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->exactly(4))
            ->method('get')
            ->with(...$this->consecutive(
                ['foist', 0],
                ['sekint', 0],
                ['thoid', 0],
                ['foiiiith', 0]
            ))->willReturnOnConsecutiveCalls(
                $this->getMockedParameterHeader('meen?', 'habibi'),
                $this->getMockedParameterHeader('meen?', 'enta'),
                null,
                null
            );
        $this->assertEquals('habibi', $part->getHeaderValue('foist'));
        $this->assertEquals('enta', $part->getHeaderValue('sekint'));
        $this->assertNull($part->getHeaderValue('thoid'));
        $this->assertEquals('WOW', $part->getHeaderValue('foiiiith', 'WOW'));
    }

    public function testGetHeaderParameter() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->exactly(4))
            ->method('get')
            ->with(...$this->consecutive(
                ['foist', 0],
                ['sekint', 0],
                ['thoid', 0],
                ['foiiiith', 0]
            ))->willReturnOnConsecutiveCalls(
                $this->getMockedParameterHeader('meen?', 'habibi', 'BING'),
                $this->getMockedParameterHeader('meen?', 'enta', 'BONG'),
                null,
                null
            );
        $this->assertEquals('BING', $part->getHeaderParameter('foist', 'eep'));
        $this->assertEquals('BONG', $part->getHeaderParameter('sekint', 'eep'));
        $this->assertNull($part->getHeaderParameter('thoid', 'eep'));
        $this->assertEquals('WOW', $part->getHeaderParameter('foiiiith', 'eep', 'WOW'));
    }

    public function testSetRawHeader() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->exactly(2))
            ->method('set')
            ->with(...$this->consecutive(
                ['title', 'SILENCE of the lamboos', 0],
                ['title', 'SILENCE of the lambies', 3]
            ));
        $observer = $this->createMock('SplObserver');
        $observer->expects($this->exactly(2))
            ->method('update');
        $part->attach($observer);

        $part->setRawHeader('title', 'SILENCE of the lamboos');
        $part->setRawHeader('title', 'SILENCE of the lambies', 3);
    }

    public function testAddRawHeader() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('add')
            ->with('title', 'SILENCE of the lamboos');
        $observer = $this->createMock('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $part->attach($observer);

        $part->addRawHeader('title', 'SILENCE of the lamboos');
    }

    public function testRemoveHeader() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('removeAll')
            ->with('weeeee');
        $observer = $this->createMock('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $part->attach($observer);

        $part->removeHeader('weeeee');
    }

    public function testRemoveSingleHeader() : void
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->exactly(2))
            ->method('remove')
            ->with(...$this->consecutive(['weeeee', 0], ['wooooo', 3]));
        $observer = $this->createMock('SplObserver');
        $observer->expects($this->exactly(2))
            ->method('update');
        $part->attach($observer);

        $part->removeSingleHeader('weeeee');
        $part->removeSingleHeader('wooooo', 3);
    }
}
