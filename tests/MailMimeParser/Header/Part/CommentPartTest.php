<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of CommentTest
 *
 * @group HeaderParts
 * @group CommentPart
 * @covers ZBateson\MailMimeParser\Header\Part\CommentPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class CommentPartTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mb;
    private $hpf;
    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $this->mb = new MbWrapper();
        $this->hpf = $this->getMockBuilder(HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $this->mb])
            ->setMethods()
            ->getMock();
    }

    private function getTokenMock(string $name) : Token
    {
        return $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$this->logger, $this->mb, $name])
            ->setMethods()
            ->getMock();
    }

    private function getQuotedMock(string $name) : QuotedLiteralPart
    {
        return $this->getMockBuilder(QuotedLiteralPart::class)
            ->setConstructorArgs([$this->logger, $this->mb, [$this->getTokenMock($name)]])
            ->setMethods()
            ->getMock();
    }

    private function newCommentPart($childParts)
    {
        return new CommentPart($this->logger, $this->mb, $this->hpf, $childParts);
    }

    public function testBasicComment() : void
    {
        $comment = 'Some silly comment made about my moustache';
        $part = $this->newCommentPart([$this->getTokenMock($comment)]);
        $this->assertEquals('', $part->getValue());
        $this->assertEquals($comment, $part->getComment());
    }

    public function testNestedCommentPartStringValue() : void
    {
        $comment = 'Some ((very) silly) "comment" made about my moustache';
        $part = $this->newCommentPart([
            $this->getTokenMock('Some '),
            $this->newCommentPart([$this->newCommentPart([$this->getTokenMock('very')]), $this->getTokenMock(' silly')]),
            $this->getTokenMock(' '),
            $this->getQuotedMock('comment'),
            $this->getTokenMock(' made about my moustache')
        ]);
        $this->assertEquals('', $part->getValue());
        $this->assertEquals($comment, $part->getComment());
    }
}
