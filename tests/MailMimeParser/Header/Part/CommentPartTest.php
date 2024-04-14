<?php

namespace ZBateson\MailMimeParser\Header\Part;

use Psr\Log\NullLogger;
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
        return $this->getMockBuilder(MimeToken::class)
            ->setConstructorArgs([$this->logger, $this->mb, $name])
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

    public function testMimeEncoding() : void
    {
        $part = $this->newCommentPart([$this->getTokenMock('=?US-ASCII?Q?Kilgore_Trout?=')]);
        $this->assertEquals('', $part->getValue());
        $this->assertEquals('Kilgore Trout', $part->getComment());
    }
}
