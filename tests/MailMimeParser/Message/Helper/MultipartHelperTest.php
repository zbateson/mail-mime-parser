<?php
namespace ZBateson\MailMimeParser\Message\Helper;

use PHPUnit\Framework\TestCase;

/**
 * MultipartHelperTest
 *
 * @group MultipartHelper
 * @group MessageHelper
 * @covers ZBateson\MailMimeParser\Message\Helper\AbstractHelper
 * @covers ZBateson\MailMimeParser\Message\Helper\MultipartHelper
 * @author Zaahid Bateson
 */
class MultipartHelperTest extends TestCase
{
    private $mockMimePartFactory;
    private $mockUUEncodedPartFactory;
    private $mockPartBuilderFactory;
    private $mockGenericHelper;

    protected function setUp(): void
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

    private function newMultipartHelper()
    {
        return new MultipartHelper(
            $this->mockMimePartFactory,
            $this->mockUUEncodedPartFactory,
            $this->mockPartBuilderFactory,
            $this->mockGenericHelper
        );
    }

    public function testGetUniqueBoundary()
    {
        $helper = $this->newMultipartHelper();
        $first = $helper->getUniqueBoundary('test');
        $second = $helper->getUniqueBoundary('test');
        $third = $helper->getUniqueBoundary('test');

        $this->assertNotEmpty($first);
        $this->assertNotEmpty($second);
        $this->assertNotEmpty($third);

        $this->assertNotEquals($first, $second);
        $this->assertNotEquals($first, $third);
        $this->assertNotEquals($second, $third);
    }

    public function testSetMimeHeaderBoundaryOnPart()
    {
        $helper = $this->newMultipartHelper();
        $part = $this->newMockMimePart();

        $part->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', $this->matchesRegularExpression('/^mime-type;\s+boundary="[^"]+"$/'));

        $helper->setMimeHeaderBoundaryOnPart($part, 'mime-type');
    }

    public function testSetMessageAsMixed()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $atts = [ $this->newMockMimePart(), $this->newMockMimePart() ];

        $part = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('hasContent')
            ->willReturn(true);
        $this->mockGenericHelper->expects($this->once())
            ->method('createNewContentPartFrom')
            ->with($message)
            ->willReturn($part);
        $message->expects($this->once())
            ->method('addChild')
            ->with($part, 0);
        $message->expects($this->once())
            ->method('getAllAttachmentParts')
            ->willReturn($atts);

        foreach ($atts as $att) {
            $att->expects($this->once())
                ->method('markAsChanged');
        }

        $helper->setMessageAsMixed($message);
    }

    public function testSetMessageAsAlternative()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $part = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('hasContent')
            ->willReturn(true);
        $this->mockGenericHelper->expects($this->once())
            ->method('createNewContentPartFrom')
            ->with($message)
            ->willReturn($part);
        $message->expects($this->once())
            ->method('addChild')
            ->with($part, 0);

        $helper->setMessageAsAlternative($message);
    }

    public function testGetContentPartContainerFromAlternative()
    {
        $helper = $this->newMultipartHelper();

        $parent = $this->newMockMimePart();
        $child1 = $this->newMockMimePart();
        $child2 = $this->newMockMimePart();
        $child3 = $this->newMockMimePart();

        $child3->method('getParent')->willReturn($child2);
        $child2->method('getParent')->willReturn($child1);
        $child1->method('getParent')->willReturn($parent);

        $parent->expects($this->once())
            ->method('getPart')
            ->willReturn($child3);
        $this->assertSame($child1, $helper->getContentPartContainerFromAlternative('test/test', $parent));
        $this->assertFalse($helper->getContentPartContainerFromAlternative('test/test', $child3));
    }

    public function testCreateAlternativeContentPart()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $contentPart = $this->newMockMimePart();
        $newPart = $this->newMockMimePart();

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
            ->willReturn($newPart);

        $message->expects($this->once())
            ->method('removePart')
            ->with($contentPart);
        $message->expects($this->once())
            ->method('addChild')
            ->with($newPart);
        $newPart->expects($this->once())
            ->method('addChild')
            ->with($contentPart);

        $this->assertEquals($newPart, $helper->createAlternativeContentPart($message, $contentPart));
    }

    public function testMoveAllPartsAsAttachmentsExcept()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $from = $this->newMockMimePart();

        $atts = [ $this->newMockMimePart(), $this->newMockMimePart() ];

        $from->expects($this->once())
            ->method('getAllParts')
            ->willReturn($atts);

        $message->expects($this->once())
            ->method('getContentType')
            ->willReturn('not-mime');
        $message->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', $this->matchesRegularExpression('/^multipart\/mixed;/'));

        $from->expects($this->exactly(2))
            ->method('removePart')
            ->withConsecutive([ $atts[0] ], [ $atts[1] ]);
        $message->expects($this->exactly(2))
            ->method('addChild')
            ->withConsecutive([ $atts[0] ], [ $atts[1] ]);

        $helper->moveAllPartsAsAttachmentsExcept($message, $from, 'test');
    }

    public function testEnforceMimeWithAttachments()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $atts = [ $this->newMockMimePart(), $this->newMockMimePart() ];

        $message->expects($this->once())
            ->method('isMime')
            ->willReturn(false);
        $message->expects($this->once())
            ->method('getAttachmentCount')
            ->willReturn(2);
        $message->expects($this->once())
            ->method('getAllAttachmentParts')
            ->willReturn($atts);
        $message->expects($this->exactly(2))
            ->method('setRawHeader')
            ->withConsecutive(
                [ 'Content-Type', $this->matchesRegularExpression('/^multipart\/mixed;/') ],
                [ 'Mime-Version', '1.0' ]
            );

        $helper->enforceMime($message);
    }

    public function testEnforceMimeWithoutAttachments()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $message->expects($this->once())
            ->method('isMime')
            ->willReturn(false);
        $message->expects($this->once())
            ->method('getAttachmentCount')
            ->willReturn(0);
        $message->expects($this->never())
            ->method('getAllAttachmentParts');
        $message->expects($this->exactly(2))
            ->method('setRawHeader')
            ->withConsecutive(
                [ 'Content-Type', "text/plain;\r\n\tcharset=\"iso-8859-1\"" ],
                [ 'Mime-Version', '1.0' ]
            );

        $helper->enforceMime($message);
    }

    public function testCreateMultipartRelatedPartForInlineChildrenOf()
    {
        $helper = $this->newMultipartHelper();

        $parent = $this->newMockMimePart();
        $related = $this->newMockMimePart();

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
            ->willReturn($related);

        $related->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', $this->matchesRegularExpression('/^multipart\/related;/'));

        $children = [ $this->newMockMimePart(), $this->newMockMimePart() ];
        $parent->expects($this->once())
            ->method('getChildParts')
            ->willReturn($children);
        $parent->expects($this->exactly(2))
            ->method('removePart')
            ->withConsecutive([ $children[0] ], [ $children[1] ]);
        $related->expects($this->exactly(2))
            ->method('addChild')
            ->withConsecutive([ $children[0] ], [ $children[1] ]);
        $parent->expects($this->once())
            ->method('addChild')
            ->with($related);

        $this->assertSame($related, $helper->createMultipartRelatedPartForInlineChildrenOf($parent));
    }

    public function testFindOtherContentPartFor()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $altPart = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('getPart')
            ->willReturn($altPart);
        $message->expects($this->once())
            ->method('isMultiPart')
            ->willReturn(true);
        $altPart
            ->method('getParent')
            ->willReturn($message);

        $message->expects($this->once())
            ->method('getChildCount')
            ->willReturn(2);

        $related = $this->newMockMimePart();
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
            ->willReturn($related);

        $message->expects($this->once())
            ->method('getChildParts')
            ->willReturn([ $this->newMockMimePart() ]);

        $helper->findOtherContentPartFor($message, 'text/html');
    }

    public function testCreateContentPartForMimeTypeWithContentInMessage()
    {
        $helper = $this->newMultipartHelper();

        $mimeType = 'test/test';
        $charset = 'test0r';

        $message = $this->newMockMessage();
        $mimePart = $this->newMockMimePart();

        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartBuilderFactory->expects($this->once())
            ->method('newPartBuilder')
            ->with($this->mockMimePartFactory)
            ->willReturn(
                $partBuilder
            );
        $partBuilder->expects($this->exactly(2))
            ->method('addHeader')
            ->withConsecutive(
                [ 'Content-Type', "$mimeType;\r\n\tcharset=\"$charset\"" ],
                [ 'Content-Transfer-Encoding', 'quoted-printable' ]
            );
        $partBuilder->expects($this->once())
            ->method('createMessagePart')
            ->willReturn($mimePart);

        $message->expects($this->once())
            ->method('isMime')
            ->willReturn(true);

        // variation: message is the content part
        $message->expects($this->once())
            ->method('getPart')
            ->willReturn($message);
        $message->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', $this->matchesRegularExpression('/^multipart\/alternative;/'));
        $message->expects($this->once())
            ->method('addChild')
            ->with($mimePart);

        $helper->createContentPartForMimeType($message, $mimeType, $charset);
    }

    public function testCreateContentPartForMimeTypeWithContentInPart()
    {
        $helper = $this->newMultipartHelper();

        $mimeType = 'test/test';
        $charset = 'test0r';

        $message = $this->newMockMessage();
        $mimePart = $this->newMockMimePart();
        $altPart = $this->newMockMimePart();

        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartBuilderFactory->expects($this->exactly(2))
            ->method('newPartBuilder')
            ->with($this->mockMimePartFactory)
            ->willReturn(
                $partBuilder
            );
        $partBuilder->expects($this->exactly(2))
            ->method('addHeader')
            ->withConsecutive(
                [ 'Content-Type', "$mimeType;\r\n\tcharset=\"$charset\"" ],
                [ 'Content-Transfer-Encoding', 'quoted-printable' ]
            );
        $partBuilder->expects($this->exactly(2))
            ->method('createMessagePart')
            ->willReturnOnConsecutiveCalls($mimePart, $altPart);

        $message->expects($this->once())
            ->method('isMime')
            ->willReturn(true);

        // variation: content is in separate part
        $contentPart = $this->newMockMimePart();
        $message->expects($this->once())
            ->method('getPart')
            ->willReturn($contentPart);
        $message->expects($this->once())
            ->method('addChild')
            ->with($altPart);

        $altPart->expects($this->exactly(2))
            ->method('addChild')
            ->withConsecutive([ $mimePart ], [ $contentPart ]);

        $helper->createContentPartForMimeType($message, $mimeType, $charset);
    }

    public function testCreateContentPartForMimeTypeInMessageWithoutContent()
    {
        $helper = $this->newMultipartHelper();

        $mimeType = 'test/test';
        $charset = 'test0r';

        $message = $this->newMockMessage();
        $mimePart = $this->newMockMimePart();

        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartBuilderFactory->expects($this->once())
            ->method('newPartBuilder')
            ->with($this->mockMimePartFactory)
            ->willReturn(
                $partBuilder
            );
        $partBuilder->expects($this->exactly(2))
            ->method('addHeader')
            ->withConsecutive(
                [ 'Content-Type', "$mimeType;\r\n\tcharset=\"$charset\"" ],
                [ 'Content-Transfer-Encoding', 'quoted-printable' ]
            );
        $partBuilder->expects($this->once())
            ->method('createMessagePart')
            ->willReturn($mimePart);

        $message->expects($this->once())
            ->method('isMime')
            ->willReturn(true);

        // variation: message does not have a content part
        $message->expects($this->once())
            ->method('getPart')
            ->willReturn(null);
        $message->expects($this->once())
            ->method('addChild')
            ->with($mimePart);

        $helper->createContentPartForMimeType($message, $mimeType, $charset);
    }

    public function testCreateAndAddPartForAttachmentToMimeMessage()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $attPart = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('isMime')
            ->willReturn(true);

        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartBuilderFactory->expects($this->once())
            ->method('newPartBuilder')
            ->with($this->mockMimePartFactory)
            ->willReturn(
                $partBuilder
            );
        $partBuilder->expects($this->exactly(3))
            ->method('addHeader')
            ->withConsecutive(
                [ 'Content-Transfer-Encoding', 'base64' ],
                [ 'Content-Type', $this->matchesRegularExpression('/^test-mime;\s+name="file.+"$/') ],
                [ 'Content-Disposition', $this->matchesRegularExpression('/^dispo;\s+filename="file.+"$/') ]
            );

        $message->expects($this->once())
            ->method('getContentType')
            ->willReturn('not-mixed');
        $message->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', $this->matchesRegularExpression('/^multipart\/mixed;/'));

        $partBuilder->expects($this->once())
            ->method('createMessagePart')
            ->willReturn($attPart);

        $resource = 'test';
        $attPart->expects($this->once())
            ->method('setContent')
            ->with($resource);
        $message->expects($this->once())
            ->method('addChild')
            ->with($attPart);

        $helper->createAndAddPartForAttachment($message, $resource, 'test-mime', 'dispo', null);
    }

    public function testCreateAndAddPartForAttachmentToMimeMessageWithDifferentEncoding()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $attPart = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('isMime')
            ->willReturn(true);

        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartBuilderFactory->expects($this->once())
            ->method('newPartBuilder')
            ->with($this->mockMimePartFactory)
            ->willReturn(
                $partBuilder
            );
        $partBuilder->expects($this->exactly(3))
            ->method('addHeader')
            ->withConsecutive(
                [ 'Content-Transfer-Encoding', 'quoted-printable' ],
                [ 'Content-Type', $this->matchesRegularExpression('/^test-mime;\s+name="file.+"$/') ],
                [ 'Content-Disposition', $this->matchesRegularExpression('/^dispo;\s+filename="file.+"$/') ]
            );

        $message->expects($this->once())
            ->method('getContentType')
            ->willReturn('not-mixed');
        $message->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', $this->matchesRegularExpression('/^multipart\/mixed;/'));

        $partBuilder->expects($this->once())
            ->method('createMessagePart')
            ->willReturn($attPart);

        $resource = 'test';
        $attPart->expects($this->once())
            ->method('setContent')
            ->with($resource);
        $message->expects($this->once())
            ->method('addChild')
            ->with($attPart);

        $helper->createAndAddPartForAttachment($message, $resource, 'test-mime', 'dispo', null, 'quoted-printable');
    }

    public function testCreateAndAddPartForAttachmentToNonMimeMessage()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $attPart = $this->newMockMimePart();

        $message->expects($this->once())
            ->method('isMime')
            ->willReturn(false);

        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartBuilderFactory->expects($this->once())
            ->method('newPartBuilder')
            ->with($this->mockUUEncodedPartFactory)
            ->willReturn(
                $partBuilder
            );
        $partBuilder->expects($this->once())
            ->method('setProperty')
            ->with('filename', 'test-file');

        $partBuilder->expects($this->once())
            ->method('createMessagePart')
            ->willReturn($attPart);

        $resource = 'test';
        $attPart->expects($this->once())
            ->method('setContent')
            ->with($resource);
        $message->expects($this->once())
            ->method('addChild')
            ->with($attPart);

        $helper->createAndAddPartForAttachment($message, $resource, 'test-mime', 'dispo', 'test-file');
    }

    public function testSetContentPartForMimeTypeThatExists()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $contPart = $this->newMockMimePart();
        $contentType = 'text/html';
        $charset = 'test-ee';

        $message->expects($this->once())
            ->method('getHtmlPart')
            ->willReturn($contPart);
        $contPart->expects($this->once())
            ->method('getContentType')
            ->willReturn($contentType);
        $contPart->expects($this->once())
            ->method('setRawHeader')
            ->with('Content-Type', "$contentType;\r\n\tcharset=\"$charset\"");

        $contPart->expects($this->once())
            ->method('setContent')
            ->with('test-content');

        $helper->setContentPartForMimeType($message, $contentType, 'test-content', $charset);
    }

    public function testSetContentPartForMimeTypeThatDoesntExists()
    {
        $helper = $this->newMultipartHelper();

        $message = $this->newMockMessage();
        $contPart = $this->newMockMimePart();
        $contentType = 'test0r';
        $charset = 'test-ee';

        $message->expects($this->once())
            ->method('getTextPart')
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
            ->willReturn($contPart);

        $contPart->expects($this->once())
            ->method('setContent')
            ->with('test-content');

        $helper->setContentPartForMimeType($message, $contentType, 'test-content', $charset);
    }
}
