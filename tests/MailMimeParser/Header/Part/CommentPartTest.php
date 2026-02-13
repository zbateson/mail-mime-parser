<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of CommentTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(CommentPart::class)]
#[CoversClass(HeaderPart::class)]
#[Group('HeaderParts')]
#[Group('CommentPart')]
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
            ->onlyMethods([])
            ->getMock();
    }

    private function getTokenMock(string $name) : Token
    {
        return $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$this->logger, $this->mb, $name])
            ->onlyMethods([])
            ->getMock();
    }

    private function getQuotedMock(string $name) : QuotedLiteralPart
    {
        return $this->getMockBuilder(QuotedLiteralPart::class)
            ->setConstructorArgs([$this->logger, $this->mb, [$this->getTokenMock($name)]])
            ->onlyMethods([])
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
