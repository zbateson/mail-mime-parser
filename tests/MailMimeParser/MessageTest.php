<?php
namespace ZBateson\MailMimeParser;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7;
use org\bovigo\vfs\vfsStream;

/**
 * Description of MessageTest
 *
 * @group MessageClass
 * @group Base
 * @covers ZBateson\MailMimeParser\Message
 * @author Zaahid Bateson
 */
class MessageTest extends TestCase
{
    private $mockPartStreamFilterManager;
    private $mockHeaderFactory;
    private $mockPartFilterFactory;
    private $mockStreamFactory;
    private $mockMessageHelperService;
    private $vfs;

    protected function setUp(): void
    {
        $this->vfs = vfsStream::setup('root');
        $this->mockPartStreamFilterManager = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartFilterFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilterFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStreamFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockMessageHelperService = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MessageHelperService')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getMockedParameterHeader($name, $value, $parameterValue = null)
    {
        $header = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'getName', 'getValueFor', 'hasParameter'])
            ->getMock();
        $header->method('getName')->willReturn($name);
        $header->method('getValue')->willReturn($value);
        $header->method('getValueFor')->willReturn($parameterValue);
        $header->method('hasParameter')->willReturn(true);
        return $header;
    }

    protected function getMockedIdHeader($id)
    {
        $header = $this->getMockBuilder('ZBateson\MailMimeParser\Header\IdHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $header->method('getId')->willReturn($id);
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

        $nestedMimePart = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->getMock();

        $nested = $this->getMockedPartBuilder();
        $nested->method('createMessagePart')
            ->willReturn($nestedMimePart);
        $children[0]->method('getChildren')
            ->willReturn([$nested]);

        foreach ($children as $key => $child) {
            // need to 'setMethods' because getAllNonFilteredParts is protected
            $childMimePart = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->setMethods([
                'getAllNonFilteredParts',
                '__destruct',
                'getContentResourceHandle',
                'getContentStream',
                'getContent',
                'getStream',
                'isTextPart',
                'getHeaderValue'
            ])
            ->getMock();

            if ($key === 0) {
                $childMimePart->expects($this->any())
                    ->method('getAllNonFilteredParts')
                    ->willReturn([$childMimePart, $nestedMimePart]);
            } else {
                $childMimePart
                    ->method('getAllNonFilteredParts')
                    ->willReturn([$childMimePart]);
            }

            $child->method('createMessagePart')
                ->willReturn($childMimePart);
        }
        $pb->method('getChildren')
            ->willReturn($children);
        return $pb;
    }

    private function newMessage($partBuilder, $stream = null, $contentStream = null)
    {
        return new Message(
            $this->mockPartStreamFilterManager,
            $this->mockStreamFactory,
            $this->mockPartFilterFactory,
            $partBuilder,
            $this->mockMessageHelperService,
            $stream,
            $stream
        );
    }

    public function testInstance()
    {
        $message = $this->newMessage(
            $this->getMockedPartBuilder()
        );
        $this->assertNotNull($message);
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message', $message);
    }

    public function testGetTextPartAndTextPartCount()
    {
        $filterMock = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->setMethods(['filter'])
            ->getMock();

        $message = $this->newMessage(
            $this->getMockedPartBuilderWithChildren()
        );
        $parts = $message->getAllParts();
        $filterMock
            ->method('filter')
            ->willReturnMap(
                [
                    [ $parts[0], false ],
                    [ $parts[1], true ],
                    [ $parts[2], false ],
                    [ $parts[3], true ],
                    [ $parts[4], false ],
                ]
            );
        $this->mockPartFilterFactory
            ->method('newFilterFromInlineContentType')
            ->willReturn($filterMock);

        $parts[1]->expects($this->once())
            ->method('getContentStream')
            ->willReturn('oufa baloufa!');
        $parts[1]->expects($this->once())
            ->method('getContent')
            ->with('charset')
            ->willReturn('shabadabada...');
        $parts[3]
            ->method('getContentStream')
            ->with('charset')
            ->willReturn('tilkomore');

        $this->assertEquals(2, $message->getTextPartCount());
        $this->assertEquals($parts[1], $message->getTextPart());
        $this->assertEquals($parts[3], $message->getTextPart(1));
        $this->assertNull($message->getTextPart(2));
        $this->assertNull($message->getTextStream(2));
        $this->assertNull($message->getTextContent(2));
        $this->assertEquals('oufa baloufa!', $message->getTextStream());
        $this->assertEquals('shabadabada...', $message->getTextContent(0, 'charset'));
        $this->assertEquals('tilkomore', $message->getTextStream(1, 'charset'));
    }

    public function testGetHtmlPartAndHtmlPartCount()
    {
        $filterMock = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->setMethods(['filter'])
            ->getMock();

        $message = $this->newMessage(
            $this->getMockedPartBuilderWithChildren()
        );
        $parts = $message->getAllParts();

        $filterMock
            ->method('filter')
            ->willReturnMap(
                [
                    [ $parts[0], false ],
                    [ $parts[1], true ],
                    [ $parts[2], false ],
                    [ $parts[3], true ],
                    [ $parts[4], false ],
                ]
            );
        $this->mockPartFilterFactory
            ->method('newFilterFromInlineContentType')
            ->willReturn($filterMock);

        $parts[1]->expects($this->once())
            ->method('getContentStream')
            ->willReturn('oufa baloufa!');
        $parts[1]->expects($this->once())
            ->method('getContent')
            ->with('charset')
            ->willReturn('shabadabada...');
        $parts[3]
            ->method('getContentStream')
            ->with('charset')
            ->willReturn('tilkomore');

        $this->assertEquals(2, $message->getHtmlPartCount());
        $this->assertEquals($parts[1], $message->getHtmlPart());
        $this->assertEquals($parts[3], $message->getHtmlPart(1));
        $this->assertNull($message->getHtmlPart(2));
        $this->assertNull($message->getHtmlStream(2));
        $this->assertNull($message->getHtmlContent(2));
        $this->assertEquals('oufa baloufa!', $message->getHtmlStream());
        $this->assertEquals('shabadabada...', $message->getHtmlContent(0, 'charset'));
        $this->assertEquals('tilkomore', $message->getHtmlStream(1, 'charset'));
    }

    public function testGetAndRemoveAttachmentParts()
    {
        $filterMock = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->setMethods(['filter'])
            ->getMock();

        $org = Psr7\stream_for('stream');
        $message = $this->newMessage(
            $this->getMockedPartBuilderWithChildren(),
            $org
        );

        // make sure MessagePart::markAsChanged is called
        $this->assertEquals($org, $message->getStream());

        $parts = $message->getAllParts();
        $filterMock
            ->method('filter')
            ->willReturnMap(
                [
                    [ $parts[0], false ],
                    [ $parts[1], true ],
                    [ $parts[2], false ],
                    [ $parts[3], true ],
                    [ $parts[4], false ],
                ]
            );
        $this->mockPartFilterFactory
            ->method('newFilterFromArray')
            ->willReturn($filterMock);

        $parts[1]->method('isTextPart')
            ->willReturn(true);
        $parts[1]->method('getHeaderValue')
            ->with('Content-Disposition', 'inline')
            ->willReturn('attachment');
        $parts[3]->method('isTextPart')
            ->willReturn(true);
        $parts[3]->method('getHeaderValue')
            ->with('Content-Disposition', 'inline')
            ->willReturn('inline');

        $this->assertEquals(1, $message->getAttachmentCount());
        $this->assertEquals([$parts[1]], $message->getAllAttachmentParts());
        $this->assertEquals($parts[1], $message->getAttachmentPart(0));
        $this->assertNull($message->getAttachmentPart(1));

        $this->mockStreamFactory->expects($this->once())
            ->method('newMessagePartStream')
            ->willReturn('changed');

        $message->removeAttachmentPart(0);
        $this->assertEquals('changed', $message->getStream());

        $this->assertEquals(0, $message->getAttachmentCount());
        $this->assertEquals(null, $message->getAttachmentPart(0));
    }

    public function testIsNotMime()
    {
        $message = $this->newMessage(
            $this->getMockedPartBuilder()
        );
        $this->assertFalse($message->isMime());
    }

    public function testIsMimeWithContentType()
    {
        $header = $this->getMockedParameterHeader('Content-Type', 'text/html', 'utf-8');

        $pb = $this->getMockedPartBuilder();
        $pb->method('getContentType')
            ->willReturn($header);
        $hc = $pb->getHeaderContainer();
        $hc->method('get')
            ->willReturnMap([ [ 'Content-Type', 0, $header ] ]);

        $message = $this->newMessage(
            $pb
        );
        $this->assertTrue($message->isMime());
    }

    public function testIsMimeWithMimeVersion()
    {
        $hf = $this->mockHeaderFactory;
        $header = $this->getMockedParameterHeader('Mime-Version', '4.3');
        $hf->method('newInstance')
            ->willReturn($header);

        $pb = $this->getMockedPartBuilder();
        $hc = $pb->getHeaderContainer();
        $hc->method('get')
            ->willReturnMap([ [ 'Mime-Version', 0, $header ] ]);

        $message = $this->newMessage(
            $pb
        );
        $this->assertTrue($message->isMime());
    }

    public function testSetAndRemoveHtmlPart()
    {
        $helper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MultipartHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->newMessage($this->getMockedPartBuilder());

        $this->mockMessageHelperService->expects($this->exactly(3))
            ->method('getMultipartHelper')
            ->willReturn($helper);
        $helper->expects($this->once())->method('setContentPartForMimeType')
            ->with($message, 'text/html', 'content', 'charset');
        $helper->expects($this->once())->method('removePartByMimeType')
            ->with($message, 'text/html', 0);
        $helper->expects($this->once())->method('removeAllContentPartsByMimeType')
            ->with($message, 'text/html', true);

        $message->setHtmlPart('content', 'charset');
        $message->removeHtmlPart();
        $message->removeAllHtmlParts();
    }

    public function testSetAndRemoveTextPart()
    {
        $helper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MultipartHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->newMessage($this->getMockedPartBuilder());

        $this->mockMessageHelperService->expects($this->exactly(3))
            ->method('getMultipartHelper')
            ->willReturn($helper);
        $helper->expects($this->once())->method('setContentPartForMimeType')
            ->with($message, 'text/plain', 'content', 'charset');
        $helper->expects($this->once())->method('removePartByMimeType')
            ->with($message, 'text/plain', 0);
        $helper->expects($this->once())->method('removeAllContentPartsByMimeType')
            ->with($message, 'text/plain', true);

        $message->setTextPart('content', 'charset');
        $message->removeTextPart();
        $message->removeAllTextParts();
    }

    public function testAddAttachmentPart()
    {
        $helper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MultipartHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->newMessage($this->getMockedPartBuilderWithChildren());
        $part = $message->getPart(2);

        $this->mockMessageHelperService->expects($this->exactly(2))
            ->method('getMultipartHelper')
            ->willReturn($helper);
        $helper->expects($this->exactly(2))->method('createAndAddPartForAttachment')
            ->withConsecutive(
                [ $message, 'content', 'mimetype', 'attachment', $this->anything(), 'base64' ],
                [ $message, $this->isInstanceOf('Psr\Http\Message\StreamInterface'), 'mimetype2', 'inline', 'blueball.png', 'base64' ]
            )
            ->willReturn($part);

        $testFile = dirname(__DIR__) . '/' . TEST_DATA_DIR . '/emails/files/blueball.png';
        $message->addAttachmentPart('content', 'mimetype');
        $message->addAttachmentPartFromFile($testFile, 'mimetype2', null, 'inline');
    }

    public function testAddAttachmentPartUsingQuotedPrintable()
    {
        $helper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MultipartHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $message = $this->newMessage($this->getMockedPartBuilderWithChildren());
        $part = $message->getPart(2);

        $this->mockMessageHelperService->expects($this->exactly(2))
            ->method('getMultipartHelper')
            ->willReturn($helper);
        $helper->expects($this->exactly(2))->method('createAndAddPartForAttachment')
            ->withConsecutive(
                [ $message, 'content', 'mimetype', 'attachment', $this->anything(), 'quoted-printable' ],
                [ $message, $this->isInstanceOf('Psr\Http\Message\StreamInterface'), 'mimetype2', 'inline', 'blueball.png', 'quoted-printable' ]
            )
            ->willReturn($part);

        $testFile = dirname(__DIR__) . '/' . TEST_DATA_DIR . '/emails/files/blueball.png';
        $message->addAttachmentPart('content', 'mimetype', null, 'attachment', 'quoted-printable');
        $message->addAttachmentPartFromFile($testFile, 'mimetype2', null, 'inline', 'quoted-printable');
    }

    public function testSigningHelperMethods()
    {
        $helper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\PrivacyHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockMessageHelperService->expects($this->exactly(4))
            ->method('getPrivacyHelper')
            ->willReturn($helper);

        $message = $this->newMessage($this->getMockedPartBuilder());

        $helper->expects($this->once())
            ->method('getSignedMessageAsString')
            ->with($message)
            ->willReturn('test');
        $helper->expects($this->once())
            ->method('getSignedMessageStream')
            ->with($message)
            ->willReturn('test');
        $helper->expects($this->once())
            ->method('setMessageAsMultipartSigned')
            ->with($message, 'micalg', 'protocol');
        $helper->expects($this->once())
            ->method('setSignature')
            ->with($message, 'signature body');

        $this->assertEquals('test', $message->getSignedMessageStream());
        $this->assertEquals('test', $message->getSignedMessageAsString());
        $message->setAsMultipartSigned('micalg', 'protocol');
        $message->setSignature('signature body');
    }
}
