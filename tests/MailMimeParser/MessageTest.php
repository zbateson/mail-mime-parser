<?php
namespace ZBateson\MailMimeParser;

use LegacyPHPUnit\TestCase;
use ZBateson\MailMimeParser\Message\PartChildrenContainer;

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
    private $mockPartStreamContainer;
    private $mockHeaderContainer;
    private $mockPartChildrenContainer;
    private $mockMultipartHelper;
    private $mockPrivacyHelper;
    
    protected function legacySetUp()
    {
        $this->mockPartStreamContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartStreamContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockHeaderContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartHeaderContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartChildrenContainer = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartChildrenContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockMultipartHelper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MultipartHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPrivacyHelper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\PrivacyHelper')
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

    protected function getMockedIMimePart()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Message\IMimePart')
            ->getMock();
    }

    protected function getChildrenContainerWithChildren()
    {
        $children = [
            $this->getMockedIMimePart(),
            $this->getMockedIMimePart(),
            $this->getMockedIMimePart()
        ];

        $nested = $this->getMockedIMimePart();
        $children[0]->method('getChildParts')
            ->willReturn([$nested]);
        $children[0]->method('getAllParts')
            ->willReturn([$children[0], $nested]);
        $children[0]->method('getChildIterator')
            ->willReturn(new \RecursiveArrayIterator([$nested]));

        $pc = new PartChildrenContainer($children);
        return $pc;
    }

    private function newMessage($childrenContainer = null)
    {
        return new Message(
            $this->mockPartStreamContainer,
            $this->mockHeaderContainer,
            ($childrenContainer) ? $childrenContainer : $this->mockPartChildrenContainer,
            $this->mockMultipartHelper,
            $this->mockPrivacyHelper
        );
    }

    public function testInstance()
    {
        $message = $this->newMessage();
        $this->assertNotNull($message);
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message', $message);
    }

    public function testGetTextPartAndTextPartCount()
    {
        $message = $this->newMessage(
            $this->getChildrenContainerWithChildren()
        );
        $this->mockHeaderContainer->method('get')->willReturn($this->getMockedParameterHeader('Content-Type', 'meep'));

        $parts = $message->getAllParts();
        $parts[1]->method('getContentType')
            ->willReturn('text/plain');
        $parts[2]->method('getContentType')
            ->willReturn('bloo');
        $parts[3]->method('getContentType')
            ->willReturn('text/plain');
        $parts[4]->method('getContentType')
            ->willReturn('Wheeep');

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
        $this->assertSame($parts[1], $message->getTextPart());
        $this->assertSame($parts[3], $message->getTextPart(1));
        $this->assertNull($message->getTextPart(2));
        $this->assertNull($message->getTextStream(2));
        $this->assertNull($message->getTextContent(2));
        $this->assertEquals('oufa baloufa!', $message->getTextStream());
        $this->assertEquals('shabadabada...', $message->getTextContent(0, 'charset'));
        $this->assertEquals('tilkomore', $message->getTextStream(1, 'charset'));
    }

    public function testGetHtmlPartAndHtmlPartCount()
    {
        $message = $this->newMessage(
            $this->getChildrenContainerWithChildren()
        );
        $this->mockHeaderContainer->method('get')->willReturn($this->getMockedParameterHeader('Content-Type', 'meep'));

        $parts = $message->getAllParts();
        $parts[1]->method('getContentType')
            ->willReturn('text/html');
        $parts[2]->method('getContentType')
            ->willReturn('bloo');
        $parts[3]->method('getContentType')
            ->willReturn('text/html');
        $parts[4]->method('getContentType')
            ->willReturn('Wheeep');

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
        $message = $this->newMessage(
            $this->getChildrenContainerWithChildren()
        );
        $this->mockHeaderContainer->method('get')->willReturn($this->getMockedParameterHeader('Content-Type', 'multipart/mixed'));

        $parts = $message->getAllParts();
        $parts[1]->method('getContentType')
            ->willReturn('text/html');
        $parts[2]->method('getContentType')
            ->willReturn('text/plain');
        $parts[3]->method('getContentType')
            ->willReturn('text/plain');
        $parts[4]->method('getContentType')
            ->willReturn('text/plain');

        $parts[1]->method('getContentDisposition')
            ->willReturn('attachment');
        $parts[2]->method('getContentDisposition')
            ->willReturn('inline');
        $parts[3]->method('isMultiPart')->willReturn(true);
        $parts[4]->method('isSignaturePart')->willReturn(true);

        $this->assertEquals(1, $message->getAttachmentCount());
        $this->assertEquals([$parts[1]], $message->getAllAttachmentParts());
        $this->assertEquals($parts[1], $message->getAttachmentPart(0));
        $this->assertNull($message->getAttachmentPart(1));

        $message->removeAttachmentPart(0);

        $this->assertEquals(0, $message->getAttachmentCount());
        $this->assertEquals(null, $message->getAttachmentPart(0));
    }

    public function testIsNotMime()
    {
        $message = $this->newMessage();
        $this->assertFalse($message->isMime());
    }

    public function testIsMimeWithContentType()
    {
        $this->mockHeaderContainer->method('get')->willReturn($this->getMockedParameterHeader('Content-Type', 'text/html', 'utf-8'));

        $message = $this->newMessage();
        $this->assertTrue($message->isMime());
    }

    public function testIsMimeWithMimeVersion()
    {
        $hf = $this->mockHeaderContainer;
        $this->mockHeaderContainer->method('get')
            ->willReturnMap([ [ 'Content-Type', 0, null ], [ 'Mime-Version', 0, $this->getMockedParameterHeader('Mime-Version', '4.3') ] ]);
        $message = $this->newMessage();
        $this->assertTrue($message->isMime());
    }

    public function testSetAndRemoveHtmlPart()
    {
        $helper = $this->mockMultipartHelper;
        $message = $this->newMessage();

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
        $helper = $this->mockMultipartHelper;
        $message = $this->newMessage();

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
        $helper = $this->mockMultipartHelper;
        $message = $this->newMessage();
        $part = $message->getPart(2);

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
        $helper = $this->mockMultipartHelper;
        $message = $this->newMessage();
        $part = $message->getPart(2);

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
        $helper = $this->mockPrivacyHelper;
        $message = $this->newMessage();

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
