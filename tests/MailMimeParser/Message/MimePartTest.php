<?php
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\MessageFilter;
use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

/**
 * Description of MimePartTest
 *
 * @group MimePart
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\MimePart
 * @covers ZBateson\MailMimeParser\Message\MultiPart
 * @covers ZBateson\MailMimeParser\Message\MessagePart
 * @author Zaahid Bateson
 */
class MimePartTest extends TestCase
{
    private $mockPartStreamContainer;
    private $mockHeaderContainer;
    private $mockPartChildrenContainer;

    protected function legacySetUp()
    {
        $this->mockPartStreamContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartStreamContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockHeaderContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartChildrenContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartChildrenContainer')
            ->getMock();
    }

    private function getMimePart($childrenContainer = null, $headerContainer = null, $streamContainer = null, $parent = null)
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
        return new MimePart($parent, $streamContainer, $headerContainer, $childrenContainer);
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
        $header->method('hasParameter')->willReturn(($parameterValue !== null));
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

    public function testGetFileName()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer->expects($this->atLeastOnce())
            ->method('get')
            ->withConsecutive(
                [ $this->equalTo('Content-Type'), 0 ],
                [ $this->equalTo('Content-Disposition'), 0 ],
                [ $this->equalTo('Content-Type'), 0 ],
                [ $this->equalTo('Content-Disposition'), 0 ],
                [ $this->equalTo('Content-Type'), 0 ],
                [ $this->equalTo('Content-Disposition'), 0 ]
            )
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

    public function testIsMimeDefaultContentTypeAndCharset()
    {
        $part = $this->getMimePart();
        $this->assertTrue($part->isMime());
        $this->assertTrue($part->isTextPart());
        $this->assertEquals('text/plain', $part->getContentType());
        $this->assertEquals('ISO-8859-1', $part->getCharset());
    }

    public function testGetContentType()
    {
        $part = $this->getMimePart();
        $header = $this->getMockedParameterHeader('content-type', 'MEEP/MOOP');
        $this->mockHeaderContainer->expects($this->once())
            ->method('get')
            ->with('Content-Type', 0)
            ->willReturn($header);
        $this->assertEquals('meep/moop', $part->getContentType());
    }

    public function testGetCharset()
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

    public function testDefaultCharset()
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

    public function testGetContentDisposition()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->method('get')
            ->with('Content-Disposition', 0)
            ->willReturn($this->getMockedParameterHeader('meen?', 'habibi'));
        $this->assertEquals('inline', $part->getContentDisposition());
    }

    public function testGetContentTransferEncoding()
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

    public function testGetContentId()
    {
        $part = $this->getMimePart();
        $header = $this->getMockedIdHeader('1337');
        $this->mockHeaderContainer
            ->method('get')
            ->willReturnOnConsecutiveCalls($header, null);
        $this->assertEquals('1337', $part->getContentId());
        $this->assertNull($part->getContentId());
    }

    public function testIsSignaturePart()
    {
        $part = $this->getMimePart();
        $this->assertFalse($part->isSignaturePart());

        $parentMimePart = $this->getMimePart();
        $parentMimePart->addChild($part);
        $this->assertFalse($part->isSignaturePart());

        $message = $this->getMockBuilder('ZBateson\MailMimeParser\Message')
            ->setConstructorArgs([
                $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartStreamContainer')->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartChildrenContainer')->getMock(),
                $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MultipartHelper')->disableOriginalConstructor()->getMock(),
                $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\PrivacyHelper')->disableOriginalConstructor()->getMock()
            ])
            ->setMethods([ 'getSignaturePart' ])
            ->getMock();
        $message->expects($this->once())->method('getSignaturePart')->willReturn($part);
        $message->addChild($part);

        $this->assertTrue($part->isSignaturePart());
    }

    public function testGetHeader()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['foist', 0],
                ['sekint', 1]
            )->willReturnOnConsecutiveCalls('giggidyfoist', 'giggidysekint');
        $this->assertEquals('giggidyfoist', $part->getHeader('foist'));
        $this->assertEquals('giggidysekint', $part->getHeader('sekint', 1));
    }

    public function testGetAllHeaders()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('getHeaderObjects')
            ->willReturn('noice');
        $this->assertEquals('noice', $part->getAllHeaders());
    }

    public function testGetAllHeadersByName()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('getAll')
            ->with('dahoida')
            ->willReturn('noice');
        $this->assertEquals('noice', $part->getAllHeadersByName('dahoida'));
    }

    public function testGetRawHeaders()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('getHeaders')
            ->willReturn('noice');
        $this->assertEquals('noice', $part->getRawHeaders());
    }

    public function testGetRawHeadersIterator()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn('noice');
        $this->assertEquals('noice', $part->getRawHeaderIterator());
    }

    public function testGetHeaderValue()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->exactly(4))
            ->method('get')
            ->withConsecutive(
                ['foist', 0],
                ['sekint', 0],
                ['thoid', 0],
                ['foiiiith', 0]
            )->willReturnOnConsecutiveCalls(
                $this->getMockedParameterHeader('meen?', 'habibi'),
                $this->getMockedParameterHeader('meen?', 'enta'),
                null
            );
        $this->assertEquals('habibi', $part->getHeaderValue('foist'));
        $this->assertEquals('enta', $part->getHeaderValue('sekint'));
        $this->assertNull($part->getHeaderValue('thoid'));
        $this->assertEquals('WOW', $part->getHeaderValue('foiiiith', 'WOW'));
    }

    public function testGetHeaderParameter()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->exactly(4))
            ->method('get')
            ->withConsecutive(
                ['foist', 0],
                ['sekint', 0],
                ['thoid', 0],
                ['foiiiith', 0]
            )->willReturnOnConsecutiveCalls(
                $this->getMockedParameterHeader('meen?', 'habibi', 'BING'),
                $this->getMockedParameterHeader('meen?', 'enta', 'BONG'),
                null
            );
        $this->assertEquals('BING', $part->getHeaderParameter('foist', 'eep'));
        $this->assertEquals('BONG', $part->getHeaderParameter('sekint', 'eep'));
        $this->assertNull($part->getHeaderParameter('thoid', 'eep'));
        $this->assertEquals('WOW', $part->getHeaderParameter('foiiiith', 'eep', 'WOW'));
    }

    public function testSetRawHeader()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [ 'title', 'SILENCE of the lamboos', 0 ],
                [ 'title', 'SILENCE of the lambies', 3 ]
            );
        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->exactly(2))
            ->method('update');
        $part->attach($observer);

        $part->setRawHeader('title', 'SILENCE of the lamboos');
        $part->setRawHeader('title', 'SILENCE of the lambies', 3);
    }

    public function testAddRawHeader()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('add')
            ->with('title', 'SILENCE of the lamboos');
        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $part->attach($observer);

        $part->addRawHeader('title', 'SILENCE of the lamboos');
    }

    public function testRemoveHeader()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->once())
            ->method('removeAll')
            ->with('weeeee');
        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $part->attach($observer);

        $part->removeHeader('weeeee');
    }

    public function testRemoveSingleHeader()
    {
        $part = $this->getMimePart();
        $this->mockHeaderContainer
            ->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive([ 'weeeee', 0 ], [ 'wooooo', 3 ]);
        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->exactly(2))
            ->method('update');
        $part->attach($observer);

        $part->removeSingleHeader('weeeee');
        $part->removeSingleHeader('wooooo', 3);
    }
}
