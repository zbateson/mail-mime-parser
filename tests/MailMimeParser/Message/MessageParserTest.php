<?php
namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7;
use org\bovigo\vfs\vfsStream;

/**
 * MessageParserTest
 *
 * @group MessageParser
 * @group Message
 * @covers ZBateson\MailMimeParser\Message\MessageParser
 * @author Zaahid Bateson
 */
class MessageParserTest extends TestCase
{
    protected $partFactoryService;
    protected $partBuilderFactory;
    protected $partStreamRegistry;
    protected $messageFactory;
    protected $uuEncodedPartFactory;
    protected $mimePartFactory;
    protected $vfs;

    protected function setUp()
    {
        $this->vfs = vfsStream::setup('root');

        $this->partFactoryService = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\PartFactoryService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->partBuilderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\PartBuilderFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->partStreamRegistry = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\PartStreamRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\MessageFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->uuEncodedPartFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\UUEncodedPartFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mimePartFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\MimePartFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getMimePartMock()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getPartBuilderMock($mimeMock = null)
    {
        if ($mimeMock === null) {
            $mimeMock = $this->getMimePartMock();
        }
        $pb = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $pb->method('createMessagePart')
            ->willReturn($mimeMock);
        return $pb;
    }

    protected function callParserWithEmail($emailText, $messageFactory, $mimePartFactory, $partBuilderFactory, $partStreamRegistry)
    {
        $email = fopen('php://memory', 'rw');
        fwrite(
            $email,
            $emailText
        );
        rewind($email);
        $parser = new MessageParser($messageFactory, $mimePartFactory, $partBuilderFactory, $partStreamRegistry);
        $parser->parse($email);
        fclose($email);
    }

    public function testParseEmptyMessage()
    {
        $content = vfsStream::newFile('part')->at($this->vfs);
        $content->withContent('');
        $handle = fopen($content->url(), 'r');

        $pfs = $this->partFactoryService;
        $pfs->method('getMessageFactory')
            ->willReturn($this->messageFactory);

        $pb = $this->getPartBuilderMock();
        $pb->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with(0);
        $pb->expects($this->once())
            ->method('canHaveHeaders');
        $pb->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $pb->expects($this->never())
            ->method('addHeader');
        $pb->expects($this->once())
            ->method('isMime')
            ->willReturn(false);
        $pb->expects($this->once())
            ->method('setStreamPartEndPos')
            ->with(0);

        $pbf = $this->partBuilderFactory;
        $pbf->method('newPartBuilder')
            ->willReturn($pb);

        $mp = new MessageParser($pfs, $pbf, $this->partStreamRegistry);
        $message = $mp->parse(Psr7\stream_for($handle));
        $this->assertNotNull($message);

        fclose($handle);
    }

    public function testParseSimpleNonMimeMessage()
    {
        $email =
            "Subject: Money owed for services rendered\r\n"
            . "\r\n";
        $startPos = strlen($email);
        $email .= "Dear Albert,\r\n\r\nAfter our wonderful time together, it's unfortunate I know, but I expect payment\r\n"
            . "for all services hereby rendered.\r\n\r\nYours faithfully,\r\nKandice Waterskyfalls";
        $endPos = strlen($email);

        $content = vfsStream::newFile('part')->at($this->vfs);
        $content->withContent($email);
        $handle = fopen($content->url(), 'r');

        $pfs = $this->partFactoryService;
        $pfs->method('getMessageFactory')
            ->willReturn($this->messageFactory);

        $pb = $this->getPartBuilderMock();
        $pb->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with(0);
        $pb->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pb->expects($this->once())
            ->method('addHeader')
            ->with('Subject', 'Money owed for services rendered');
        $pb->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $pb->expects($this->once())
            ->method('isMime')
            ->willReturn(false);
        $pb->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($startPos);
        $pb->expects($this->exactly(7))
            ->method('setStreamPartAndContentEndPos')
            ->withConsecutive([$this->anything()], [$this->anything()], [$this->anything()],
                [$this->anything()], [$this->anything()], [$this->anything()], [$endPos]);
        $pb->expects($this->once())
            ->method('setStreamPartEndPos')
            ->with($endPos);

        $pbf = $this->partBuilderFactory;
        $pbf->method('newPartBuilder')
            ->willReturn($pb);

        $mp = new MessageParser($pfs, $pbf, $this->partStreamRegistry);
        $message = $mp->parse(Psr7\stream_for($handle));
        $this->assertNotNull($message);

        fclose($handle);
    }

    public function testParseMessageWithUUEncodedAttachments()
    {
        $email =
            "Subject: The Diamonds\r\n"
            . "To: Cousin Avi\r\n"
            . "\r\n";
        $startPos = strlen($email);
        $email .= "Aviiiiiiiiii...\r\n\r\n";
        $contentEnd = strlen($email);
        $email .= "begin 666 message.txt\r\n"
            . 'Listen to me... if the stones are kosher, then I\'ll buy them, won\'t I?'
            . "\r\nend\r\n\r\n";
        $endPos = strlen($email);
        $startPos2 = $endPos;
        $email .= "begin 600 message2.txt\r\n"
            . 'No, Tommy. ... It\'s tiptop. It\'s just I\'m not sure about the colour.'
            . "\r\nend\r\n";
        $endEmailPos = strlen($email);

        $content = vfsStream::newFile('part')->at($this->vfs);
        $content->withContent($email);
        $handle = fopen($content->url(), 'r');

        $pfs = $this->partFactoryService;
        $pfs->method('getMessageFactory')
            ->willReturn($this->messageFactory);
        $pfs->expects($this->exactly(2))
            ->method('getUUEncodedPartFactory')
            ->willReturn($this->uuEncodedPartFactory);

        $pbm = $this->getPartBuilderMock();
        $pbm->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with(0);
        $pbm->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pbm->expects($this->exactly(2))
            ->method('addHeader')
            ->withConsecutive(
                ['Subject', 'The Diamonds'],
                ['To', 'Cousin Avi']
            );
        $pbm->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $pbm->expects($this->once())
            ->method('isMime')
            ->willReturn(false);
        $pbm->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($startPos);
        $pbm->expects($this->exactly(2))
            ->method('setStreamPartAndContentEndPos')
            ->withConsecutive([$this->anything()], [$contentEnd]);
        $pbm->expects($this->once())
            ->method('setStreamPartEndPos')
            ->with($endEmailPos);

        $pba1 = $this->getPartBuilderMock();
        $pba1->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with($contentEnd);
        $pba1->expects($this->exactly(2))
            ->method('setProperty')
            ->withConsecutive(
                ['mode', '666'],
                ['filename', 'message.txt']
            );
        $pba1->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($contentEnd);
        $pba1->expects($this->exactly(4))
            ->method('setStreamPartAndContentEndPos')
            ->withConsecutive([$this->anything()], [$this->anything()],
                [$this->anything()], [$endPos]);

        $pba2 = $this->getPartBuilderMock();
        $pba2->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with($startPos2);
        $pba2->expects($this->exactly(2))
            ->method('setProperty')
            ->withConsecutive(
                ['mode', '600'],
                ['filename', 'message2.txt']
            );
        $pba2->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($startPos2);
        $pba2->expects($this->exactly(3))
            ->method('setStreamPartAndContentEndPos')
            ->withConsecutive([$this->anything()], [$this->anything()],
                [$endEmailPos]);

        $pbm->expects($this->exactly(2))
            ->method('addChild')
            ->withConsecutive([$pba1], [$pba2]);

        $pbf = $this->partBuilderFactory;
        $pbf->expects($this->exactly(3))
            ->method('newPartBuilder')
            ->willReturnOnConsecutiveCalls(
                $pbm, $pba1, $pba2
            );

        $mp = new MessageParser($pfs, $pbf, $this->partStreamRegistry);
        $message = $mp->parse(Psr7\stream_for($handle));
        $this->assertNotNull($message);

        fclose($handle);
    }

    public function testParseSimpleMimeMessage()
    {
        $email =
            "Subject: Money owed for services rendered\n"
            . "Content-Type: text/html\n"
            . "\n";
        $startPos = strlen($email);
        $email .= "Dear Albert,\r\n\r\nAfter our wonderful time together, it's unfortunate I know, but I expect payment\r\n"
            . "for all services hereby rendered.\r\n\r\nYours faithfully,\r\nKandice Waterskyfalls";
        $endPos = strlen($email);

        $content = vfsStream::newFile('part')->at($this->vfs);
        $content->withContent($email);
        $handle = fopen($content->url(), 'r');

        $pfs = $this->partFactoryService;
        $pfs->method('getMessageFactory')
            ->willReturn($this->messageFactory);

        $pb = $this->getPartBuilderMock();
        $pb->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with(0);
        $pb->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pb->expects($this->exactly(2))
            ->method('addHeader')
            ->withConsecutive(
                ['Subject', 'Money owed for services rendered'],
                ['Content-Type', 'text/html']
            );
        $pb->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $pb->expects($this->once())
            ->method('isMime')
            ->willReturn(true);
        $pb->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($startPos);
        $pb->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($endPos);

        $pbf = $this->partBuilderFactory;
        $pbf->method('newPartBuilder')
            ->willReturn($pb);

        $mp = new MessageParser($pfs, $pbf, $this->partStreamRegistry);
        $message = $mp->parse(Psr7\stream_for($handle));
        $this->assertNotNull($message);

        fclose($handle);
    }

    public function testParseMultipartAlternativeMessage()
    {
        $email =
            "Content-Type: multipart/alternative;\r\n"
            . " boundary=balderdash\r\n"
            . "Subject: I'm a tiny little wee teapot\r\n"
            . "\r\n";

        $messagePartStart = strlen($email);
        $email .= "--balderdash\r\n";
        $partOneStart = strlen($email);
            $email .= "Content-Type: text/html\r\n"
            . "\r\n";
        $partOneContentStart = strlen($email);
        $email .=
            "<p>I'm a little teapot, short and stout.  Where is my guiness, where is"
            . "my draught.  I certainly can't rhyme, but no I'm not daft.</p>\r\n";
        $partOneEnd = strlen($email);
        $email .= "\r\n--balderdash\r\n";
        $partTwoStart = strlen($email);
        $email .= "Content-Type: text/plain\r\n"
            . "\r\n";
        $partTwoContentStart = strlen($email);
        $email .=
            "I'm a little teapot, short and stout.  Where is my guiness, where is"
            . "my draught.  I certainly can't rhyme, but no I'm not daft.";
        $partTwoEnd = strlen($email);
        $email .= "\r\n--balderdash--\r\n\r\n";
        $emailEnd = strlen($email);

        $content = vfsStream::newFile('part')->at($this->vfs);
        $content->withContent($email);
        $handle = fopen($content->url(), 'r');

        $pfs = $this->partFactoryService;
        $pfs->method('getMessageFactory')
            ->willReturn($this->messageFactory);
        $pfs->expects($this->exactly(3))
            ->method('getMimePartFactory')
            ->willReturn($this->mimePartFactory);

        $pbm = $this->getPartBuilderMock();
        $pbm->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with(0);
        $pbm->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pbm->expects($this->exactly(2))
            ->method('addHeader')
            ->withConsecutive(
                ['Content-Type', "multipart/alternative;\r\n boundary=balderdash"],
                ['Subject', 'I\'m a tiny little wee teapot']
            );
        $pbm->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $pbm->expects($this->once())
            ->method('isMime')
            ->willReturn(true);
        $pbm->expects($this->once())
            ->method('isMultiPart')
            ->willReturn(true);
        $pbm->expects($this->once())
            ->method('setEndBoundaryFound')
            ->with('--balderdash')
            ->willReturn(true);
        $pbm->expects($this->exactly(4))
            ->method('isParentBoundaryFound')
            ->willReturnOnConsecutiveCalls(false, false, false, true);
        $pbm->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($messagePartStart);
        $pbm->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($messagePartStart);

        $pba1 = $this->getPartBuilderMock();
        $pba1->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pba1->expects($this->once())
            ->method('addHeader')
            ->with('Content-Type', 'text/html');
        $pba1->expects($this->once())
            ->method('getParent')
            ->willReturn($pbm);
        $pba1->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->willReturnMap([
                [$this->anything(), false],
                ['--balderdash', true]
            ]);
        $pba1->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with($partOneStart);
        $pba1->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($partOneContentStart);
        $pba1->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($partOneEnd);

        $pba2 = $this->getPartBuilderMock();
        $pba2->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pba2->expects($this->once())
            ->method('addHeader')
            ->with('Content-Type', 'text/plain');
        $pba2->expects($this->once())
            ->method('getParent')
            ->willReturn($pbm);
        $pba2->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->willReturnMap([
                [$this->anything(), false],
                ['--balderdash--', true]
            ]);
        $pba2->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with($partTwoStart);
        $pba2->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($partTwoContentStart);
        $pba2->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($partTwoEnd);

        $pba3 = $this->getPartBuilderMock();
        $pba3->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(false);
        $pba3->expects($this->once())
            ->method('getParent')
            ->willReturn($pbm);
        $pba3->expects($this->once())
            ->method('setEof');
        $pba3->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($emailEnd);

        $pbm->expects($this->exactly(3))
            ->method('addChild')
            ->withConsecutive([$pba1], [$pba2], [$pba3]);

        $pbf = $this->partBuilderFactory;
        $pbf->expects($this->exactly(4))
            ->method('newPartBuilder')
            ->willReturnOnConsecutiveCalls(
                $pbm, $pba1, $pba2, $pba3
            );

        $mp = new MessageParser($pfs, $pbf, $this->partStreamRegistry);
        $message = $mp->parse(Psr7\stream_for($handle));
        $this->assertNotNull($message);

        fclose($handle);
    }

    public function testParseMultipartMixedWithAlternativeMessage()
    {
        $email =
            "Content-Type: multipart/mixed; boundary=balderdash\r\n"
            . "Subject: Of mice and men\r\n"
            . "\r\n";

        $messagePartStart = strlen($email);
        $email .= "This existed for nought - hidden from view";
        $messagePartEnd = strlen($email);

        $email .= "\r\n--balderdash\r\n";
        $altPartStart = strlen($email);
        $email .= "Content-Type: multipart/alternative; boundary=gobbledygook\r\n"
            . "\r\n";

        $altPartContentStart = strlen($email);
        $email .= "A line to fool the senses was created... and it was this line";
        $altPartContentEnd = strlen($email);

        $email .= "\r\n--gobbledygook\r\n";
        $partOneStart = strlen($email);
        $email .= "Content-Type: text/html\r\n"
            . "\r\n";
        $partOneContentStart = strlen($email);
        $email .=
            "<p>There once was a man, who was both man and mouse.  He thought himself"
            . "pretty, but was really - well - as ugly as you can imagine a creature"
            . "that is part man and part mouse.</p>";
        $partOneEnd = strlen($email);
        $email .= "\r\n--gobbledygook\r\n";
        $partTwoStart = strlen($email);
        $email .= "Content-Type: text/plain\r\n"
            . "\r\n";
        $partTwoContentStart = strlen($email);
        $email .=
            "There once was a man, who was both man and mouse.  He thought himself"
            . "pretty, but was really - well - as ugly as you can imagine a creature"
            . "that is part man and part mouse.";
        $partTwoEnd = strlen($email);
        $email .= "\r\n--gobbledygook--";
        $email .= "\r\n--balderdash\r\n";
        $partThreeStart = strlen($email);
        $email .= "Content-Type: text/html\r\n"
            . "\r\n";
        $partThreeContentStart = strlen($email);
        $email .= "<p>He wandered through the lands, and shook fancy hands.</p>";
        $partThreeEnd = strlen($email);
        $email .= "\r\n--balderdash\r\n";
        $partFourStart = strlen($email);
        $email .= "\r\n";
        $partFourContentStart = strlen($email);
        $email .= " (^^) ";
        $partFourEnd = strlen($email);
        $email .= "\r\n--balderdash--\r\n";
        $emailEnd = strlen($email);

        $content = vfsStream::newFile('part')->at($this->vfs);
        $content->withContent($email);
        $handle = fopen($content->url(), 'r');

        $pfs = $this->partFactoryService;
        $pfs->method('getMessageFactory')
            ->willReturn($this->messageFactory);
        $pfs->expects($this->exactly(7))
            ->method('getMimePartFactory')
            ->willReturn($this->mimePartFactory);

        $pbm = $this->getPartBuilderMock();
        $pbm->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with(0);
        $pbm->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pbm->expects($this->exactly(2))
            ->method('addHeader')
            ->withConsecutive(
                ['Content-Type', "multipart/mixed; boundary=balderdash"],
                ['Subject', 'Of mice and men']
            );
        $pbm->expects($this->once())
            ->method('isMime')
            ->willReturn(true);
        $pbm->expects($this->once())
            ->method('isMultiPart')
            ->willReturn(true);
        $pbm->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->withConsecutive(
                ['This existed for nought - hidden from view'],
                ['--balderdash']
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $pbm->expects($this->exactly(5))
            ->method('isParentBoundaryFound')
            ->willReturnOnConsecutiveCalls(false, false, false, false, true);
        $pbm->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($messagePartStart);
        $pbm->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($messagePartEnd);

        $pbAlt = $this->getPartBuilderMock();
        $pbAlt->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pbAlt->expects($this->once())
            ->method('getParent')
            ->willReturn($pbm);
        $pbAlt->expects($this->once())
            ->method('addHeader')
            ->with('Content-Type', 'multipart/alternative; boundary=gobbledygook');
        $pbAlt->expects($this->once())
            ->method('isMultiPart')
            ->willReturn(true);
        $pbAlt->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->withConsecutive(
                ['A line to fool the senses was created... and it was this line'],
                ['--gobbledygook']
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $pbAlt->expects($this->exactly(4))
            ->method('isParentBoundaryFound')
            ->willReturnOnConsecutiveCalls(false, false, false, true);
        $pbAlt->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with($altPartStart);
        $pbAlt->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($altPartContentStart);
        $pbAlt->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($altPartContentEnd);

        $pba1 = $this->getPartBuilderMock();
        $pba1->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pba1->expects($this->once())
            ->method('getParent')
            ->willReturn($pbAlt);
        $pba1->expects($this->once())
            ->method('addHeader')
            ->with('Content-Type', 'text/html');
        $pba1->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->withConsecutive(
                ["<p>There once was a man, who was both man and mouse.  He thought himself"
                . "pretty, but was really - well - as ugly as you can imagine a creature"
                . "that is part man and part mouse.</p>"],
                ['--gobbledygook']
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $pba1->expects($this->once())
            ->method('isMultiPart')
            ->willReturn(false);
        $pba1->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with($partOneStart);
        $pba1->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($partOneContentStart);
        $pba1->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($partOneEnd);

        $pba2 = $this->getPartBuilderMock();
        $pba2->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pba2->expects($this->once())
            ->method('getParent')
            ->willReturn($pbAlt);
        $pba2->expects($this->once())
            ->method('addHeader')
            ->with('Content-Type', 'text/plain');
        $pba2->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->withConsecutive(
                ["There once was a man, who was both man and mouse.  He thought himself"
                . "pretty, but was really - well - as ugly as you can imagine a creature"
                . "that is part man and part mouse."],
                ['--gobbledygook--']
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $pba2->expects($this->once())
            ->method('isMultiPart')
            ->willReturn(false);
        $pba2->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with($partTwoStart);
        $pba2->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($partTwoContentStart);
        $pba2->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($partTwoEnd);

        $pba3 = $this->getPartBuilderMock();
        $pba3->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(false);
        $pba3->expects($this->once())
            ->method('getParent')
            ->willReturn($pbAlt);
        $pba3->expects($this->once())
            ->method('isMultiPart')
            ->willReturn(false);
        $pba3->expects($this->once())
            ->method('setEndBoundaryFound')
            ->with('--balderdash')
            ->willReturn(true);

        $pba4 = $this->getPartBuilderMock();
        $pba4->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pba4->expects($this->once())
            ->method('getParent')
            ->willReturn($pbm);
        $pba4->expects($this->once())
            ->method('addHeader')
            ->with('Content-Type', 'text/html');
        $pba4->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->withConsecutive(
                ['<p>He wandered through the lands, and shook fancy hands.</p>'],
                ['--balderdash']
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $pba4->expects($this->once())
            ->method('isMultiPart')
            ->willReturn(false);
        $pba4->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with($partThreeStart);
        $pba4->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($partThreeContentStart);
        $pba4->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($partThreeEnd);

        $pba5 = $this->getPartBuilderMock();
        $pba5->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pba5->expects($this->once())
            ->method('getParent')
            ->willReturn($pbm);
        $pba5->expects($this->once())
            ->method('isMultiPart')
            ->willReturn(false);
        $pba5->expects($this->never())
            ->method('addHeader');
        $pba5->expects($this->exactly(2))
            ->method('setEndBoundaryFound')
            ->withConsecutive(
                [' (^^) '],
                ['--balderdash--']
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $pba5->expects($this->once())
            ->method('isMultiPart')
            ->willReturn(false);
        $pba5->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with($partFourStart);
        $pba5->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($partFourContentStart);
        $pba5->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($partFourEnd);

        $pba6 = $this->getPartBuilderMock();
        $pba6->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(false);
        $pba6->expects($this->once())
            ->method('getParent')
            ->willReturn($pbm);
        $pba6->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($emailEnd);
        $pba6->expects($this->once())
            ->method('setEof');
        // no extra trailling characters
        $pba6->expects($this->never())
            ->method('setEndBoundaryFound');

        $pbm->expects($this->any())
            ->method('addChild')
            ->withConsecutive([$pbAlt], [$pba4], [$pba5], [$pba6]);
        $pbAlt->expects($this->exactly(3))
            ->method('addChild')
            ->withConsecutive([$pba1], [$pba2], [$pba3]);

        $pbf = $this->partBuilderFactory;
        $pbf->expects($this->exactly(8))
            ->method('newPartBuilder')
            ->willReturnOnConsecutiveCalls(
                $pbm, $pbAlt, $pba1, $pba2, $pba3, $pba4, $pba5, $pba6
            );

        $mp = new MessageParser($pfs, $pbf, $this->partStreamRegistry);
        $message = $mp->parse(Psr7\stream_for($handle));
        $this->assertNotNull($message);

        fclose($handle);
    }

    public function testParseMimeMessageWithLongHeader()
    {
        $email =
            "Subject: Money owed for services rendered\r\n"
            . "Content-Type: text/html\r\n"
            // Exactly 2 before the "\r\n" position
            . "X-Long-Header-1: " . str_repeat('A', 4096 - 22) . "\r\n\tABC\r\n"
            // Exactly 1 before the "\r\n" position
            . "X-Long-Header-2: " . str_repeat('A', 4096 - 21) . "\r\n\tABC\r\n"
            // Exactly before the "\r\n" position
            . "X-Long-Header-3: " . str_repeat('A', 4096 - 20) . "\r\n\tABC\r\n"
            // In the middle of "\r\n"
            . "X-Long-Header-4: " . str_repeat('A', 4096 - 19) . "\r\n\tABC\r\n"
            // Exactly at the "\r\n" position, so next readline would be empty
            . "X-Long-Header-5: " . str_repeat('A', 4096 - 18) . "\r\n\tABC\r\n"
            // additional characters should be dumped, in this case 1 char
            . "X-Long-Header-6: " . str_repeat('A', 4096 - 17) . "\r\n\tABC\r\n"
            // additional characters should be dumped, in this case 18 chars
            . "X-Long-Header-7: " . str_repeat('A', 4096) . "\r\n\tABC\r\n"
            . "X-Test-Header: test-value\r\n"
            . "\r\n";

        $startPos = strlen($email);
        $email .= "Dear Albert,\r\n\r\nAfter our wonderful time together, it's unfortunate I know, but I expect payment\r\n"
            . "for all services hereby rendered.\r\n\r\nYours faithfully,\r\nKandice Waterskyfalls";
        $endPos = strlen($email);

        $content = vfsStream::newFile('part')->at($this->vfs);
        $content->withContent($email);
        $handle = fopen($content->url(), 'r');

        $pfs = $this->partFactoryService;
        $pfs->method('getMessageFactory')
            ->willReturn($this->messageFactory);

        $pb = $this->getPartBuilderMock();
        $pb->expects($this->once())
            ->method('setStreamPartStartPos')
            ->with(0);
        $pb->expects($this->once())
            ->method('canHaveHeaders')
            ->willReturn(true);
        $pb->expects($this->exactly(10))
            ->method('addHeader')
            ->withConsecutive(
                ['Subject', 'Money owed for services rendered'],
                ['Content-Type', 'text/html'],
                ['X-Long-Header-1', str_repeat('A', 4096 - 22) . "\r\n\tABC"],
                ['X-Long-Header-2', str_repeat('A', 4096 - 21) . "\r\n\tABC"],
                ['X-Long-Header-3', str_repeat('A', 4096 - 20) . "\r\n\tABC"],
                ['X-Long-Header-4', str_repeat('A', 4096 - 19) . "\r\n\tABC"],
                ['X-Long-Header-5', str_repeat('A', 4096 - 18) . "\r\n\tABC"],
                ['X-Long-Header-6', str_repeat('A', 4096 - 18) . "\r\n\tABC"],
                ['X-Long-Header-7', str_repeat('A', 4096 - 18) . "\r\n\tABC"],
                ['X-Test-Header', 'test-value']
            );
        $pb->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $pb->expects($this->once())
            ->method('isMime')
            ->willReturn(true);
        $pb->expects($this->once())
            ->method('setStreamContentStartPos')
            ->with($startPos);
        $pb->expects($this->once())
            ->method('setStreamPartAndContentEndPos')
            ->with($endPos);

        $pbf = $this->partBuilderFactory;
        $pbf->method('newPartBuilder')
            ->willReturn($pb);

        $mp = new MessageParser($pfs, $pbf, $this->partStreamRegistry);
        $message = $mp->parse(Psr7\stream_for($handle));
        $this->assertNotNull($message);

        fclose($handle);
    }
}
