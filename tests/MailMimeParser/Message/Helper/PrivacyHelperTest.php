<?php

namespace ZBateson\MailMimeParser\Message\Helper;

use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

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
    // @phpstan-ignore-next-line
    private $mockMimePartFactory;

    // @phpstan-ignore-next-line
    private $mockUUEncodedPartFactory;

    // @phpstan-ignore-next-line
    private $mockGenericHelper;

    // @phpstan-ignore-next-line
    private $mockMultipartHelper;

    protected function setUp() : void
    {
        $this->mockMimePartFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Factory\IMimePartFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUUEncodedPartFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Factory\IUUEncodedPartFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockGenericHelper = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Helper\GenericHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockMultipartHelper = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\Helper\MultipartHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newMockIMimePart() : \ZBateson\MailMimeParser\Message\IMimePart
    {
        return $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Message\IMimePart::class);
    }

    private function newMockIMessage() : \ZBateson\MailMimeParser\IMessage
    {
        return $this->getMockForAbstractClass(\ZBateson\MailMimeParser\IMessage::class);
    }

    private function newPrivacyHelper() : PrivacyHelper
    {
        return new PrivacyHelper(
            $this->mockMimePartFactory,
            $this->mockUUEncodedPartFactory,
            $this->mockGenericHelper,
            $this->mockMultipartHelper
        );
    }

    public function testOverwrite8bitContentEncoding() : void
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockIMessage();
        $partText = $this->newMockIMimePart();
        $partNonText = $this->newMockIMimePart();

        $message->expects($this->once())
            ->method('getAllParts')
            ->willReturn([$partText, $partNonText]);

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

    public function testEnsureHtmlPartFirstForSignedMessage() : void
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockIMessage();
        $alt = $this->newMockIMimePart();
        $cont = $this->newMockIMimePart();
        $children = [$this->newMockIMimePart(), $cont];

        $message->expects($this->once())
            ->method('getPartByMimeType')
            ->with('multipart/alternative')
            ->willReturn($alt);

        $this->mockMultipartHelper->expects($this->once())
            ->method('getContentPartContainerFromAlternative')
            ->willReturn($cont);
        $alt->expects($this->once())
            ->method('getChildCount')
            ->willReturn(2);
        $alt->expects($this->once())
            ->method('getChildParts')
            ->willReturn($children);

        $alt->expects($this->once())
            ->method('removePart')
            ->with($children[0]);
        $alt->expects($this->once())
            ->method('addChild')
            ->with($children[0]);

        $helper->ensureHtmlPartFirstForSignedMessage($message);
    }

    public function testSetSignature() : void
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockIMessage();
        $signedPart = $this->newMockIMimePart();

        $message->expects($this->once())
            ->method('getSignaturePart')
            ->willReturn(null);

        $this->mockMimePartFactory
            ->expects($this->once())
            ->method('newInstance')
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

    public function testSetMessageAsMultipartSigned() : void
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockIMessage();
        $messagePart = $this->newMockIMimePart();

        $message->expects($this->once())
            ->method('getContentType')
            ->willReturn('text/plain');

        $this->mockMultipartHelper->expects($this->once())
            ->method('enforceMime')
            ->willReturn($message);

        $this->mockMimePartFactory
            ->expects($this->once())
            ->method('newInstance')
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
        // called from ensureHtmlPartFirstForSignedMessage
        $message->expects($this->once())
            ->method('getPartByMimeType')
            ->with('multipart/alternative')
            ->willReturn(null);
        // called from setSignature
        $message->expects($this->once())
            ->method('getSignaturePart')
            ->willReturn($this->newMockIMimePart());

        $helper->setMessageAsMultipartSigned($message, 'my-micalg', 'l33t-protocol');
    }

    public function testSignedMessageStream() : void
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockIMessage();
        $part = $this->newMockIMimePart();

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

    public function testSignedMessageAsString() : void
    {
        $helper = $this->newPrivacyHelper();

        $message = $this->newMockIMessage();
        $part = $this->newMockIMimePart();

        $message->expects($this->exactly(2))
            ->method('getChild')
            ->with(0)
            ->willReturnOnConsecutiveCalls(null, $part);
        $part->expects($this->once())
            ->method('getStream')
            ->willReturn(Psr7\Utils::streamFor("test\rwith\nnew\r\nlines"));

        $this->assertNull($helper->getSignedMessageAsString($message));
        $this->assertEquals("test\r\nwith\r\nnew\r\nlines", $helper->getSignedMessageAsString($message));
    }
}
