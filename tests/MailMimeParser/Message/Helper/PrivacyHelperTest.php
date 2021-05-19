<?php
namespace ZBateson\MailMimeParser\Message\Helper;

use GuzzleHttp\Psr7;
use LegacyPHPUnit\TestCase;

/**
 * PrivacyHelperTest
 *
 * @group PrivacyHelper
 * @group MessageHelper
 * @covers ZBateson\MailMimeParser\Message\Helper\AbstractHelper
 * @covers ZBateson\MailMimeParser\Message\Helper\PrivacyHelper
 * @author Zaahid Bateson
 */
class PrivacyHelperTest extends TestCase
{
    private $mockMimePartFactory;
    private $mockUUEncodedPartFactory;
    private $mockPartBuilderFactory;
    private $mockGenericHelper;
    private $mockMultipartHelper;

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
        $this->mockGenericHelper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\GenericHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockMultipartHelper = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MultipartHelper')
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

    private function newPrivacyHelper()
    {
        return new PrivacyHelper(
            $this->mockMimePartFactory,
            $this->mockUUEncodedPartFactory,
            $this->mockPartBuilderFactory,
            $this->mockGenericHelper,
            $this->mockMultipartHelper
        );
    }

    public function testOverwrite8bitContentEncoding()
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockMessage();
        $partText = $this->newMockMimePart();
        $partNonText = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('getAllParts')
            ->willReturn([ $partText, $partNonText ]);

        $partText->expects($this->once())
            ->method('getContentType')
            ->willReturn('text/plain');
        $partNonText->expects($this->once())
            ->method('getContentType')
            ->willReturn('something else entirely');

        $partText->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Transfer-Encoding', 'quoted-printable');
        $partNonText->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Transfer-Encoding', 'base64');

        $helper->overwrite8bitContentEncoding($message);
    }

    public function testSetSignature()
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockMessage();
        $signedPart = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('getSignaturePart')
            ->willReturn(null);

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
            ->willReturn($signedPart);

        $message->expects($this->once())
            ->method('addChild')
            ->with($signedPart);
        $message->expects($this->once())
            ->method('getHeaderParameter')
            ->with('Content-Type', 'protocol')
            ->willReturn('the-meatiest');

        $signedPart->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', 'the-meatiest');
        $signedPart->expects($this->once())
            ->method('setContent')
            ->with('much-signature');

        $helper->setSignature($message, 'much-signature');
    }

    public function testSetMessageAsMultipartSigned()
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockMessage();
        $messagePart = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('getContentType')
            ->willReturn('text/plain');

        $this->mockMultipartHelper->expects($this->once())
            ->method('enforceMime')
            ->willReturn($message);

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
            ->willReturn($messagePart);

        $this->mockGenericHelper->expects($this->once())
            ->method('movePartContentAndChildren')
            ->with($message, $messagePart);

        $message->expects($this->once())
            ->method('addChild')
            ->willReturn($messagePart);
        $this->mockMultipartHelper->expects($this->once())
            ->method('getUniqueBoundary')
            ->with('multipart/signed')
            ->willReturn('a-unique-boundary');
        $message->expects($this->once())
            ->method('setRawHeader')
            ->with(
                'Content-Type',
                "multipart/signed;\r\n\tboundary=\"a-unique-boundary\";\r\n\tmicalg=\"my-micalg\"; protocol=\"l33t-protocol\""
            );

        // called from overwrite8bitContentEncoding
        $message->expects($this->once())
            ->method('getAllParts')
            ->willReturn([]);
        // called from setSignature
        $message->expects($this->once())
            ->method('getSignaturePart')
            ->willReturn($this->newMockMimePart());

        $helper->setMessageAsMultipartSigned($message, 'my-micalg', 'l33t-protocol');
    }

    public function testSignedMessageStream()
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockMessage();
        $part = $this->newMockMimePart();

        $message->expects($this->exactly(2))
            ->method('getChild')
            ->with(0)
            ->willReturnOnConsecutiveCalls(null, $part);
        $part->expects($this->once())
            ->method('getStream')
            ->willReturn('test');

        $this->assertNull($helper->getSignedMessageStream($message));
        $this->assertEquals('test', $helper->getSignedMessageStream($message));
    }

    public function testSignedMessageAsString()
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockMessage();
        $part = $this->newMockMimePart();

        $message->expects($this->exactly(2))
            ->method('getChild')
            ->with(0)
            ->willReturnOnConsecutiveCalls(null, $part);
        $part->expects($this->once())
            ->method('getStream')
            ->willReturn(Psr7\stream_for("test\rwith\nnew\r\nlines"));

        $this->assertNull($helper->getSignedMessageAsString($message));
        $this->assertEquals("test\r\nwith\r\nnew\r\nlines", $helper->getSignedMessageAsString($message));
    }

    public function testGetSignaturePart()
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockMessage();
        $message->expects($this->exactly(2))
            ->method('getContentType')
            ->willReturnOnConsecutiveCalls('naffing', 'multipart/signed');
        $message->expects($this->once())
            ->method('getChild')
            ->with(1)
            ->willReturn('a-signature');

        $this->assertNull($helper->getSignaturePart($message));
        $this->assertEquals('a-signature', $helper->getSignaturePart($message));
    }
}
