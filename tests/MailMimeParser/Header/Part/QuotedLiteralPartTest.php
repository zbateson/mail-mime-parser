<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of QuotedLiteralPart
 *
 * @group HeaderParts
 * @group QuotedLiteralPart
 * @covers ZBateson\MailMimeParser\Header\Part\QuotedLiteralPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class QuotedLiteralPartTest extends TestCase
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

    private function getTokenArray(string ...$args) : array
    {
        return \array_map(
            fn ($a) => $this->getMockBuilder(Token::class)
                ->setConstructorArgs([$this->logger, $this->mb, $a, false, true])
                ->setMethods()
                ->getMock(),
            $args
        );
    }

    private function getCommentPart(string $comment) : CommentPart
    {
        return $this->getMockBuilder(CommentPart::class)
            ->setConstructorArgs([$this->logger, $this->mb, $this->hpf, $this->getTokenArray($comment)])
            ->setMethods()
            ->getMock();
    }

    private function newQuotedLiteralPart($childParts) : QuotedLiteralPart
    {
        return new QuotedLiteralPart($this->logger, $this->mb, $childParts);
    }

    public function testInstance() : void
    {
        $part = $this->newQuotedLiteralPart($this->getTokenArray('Kilgore Trout'));
        $this->assertNotNull($part);
        $this->assertEquals('Kilgore Trout', $part->getValue());
    }

    public function testNotIgnorableSpaces() : void
    {
        $part = $this->newQuotedLiteralPart($this->getTokenArray(' ', 'Kilgore', ' ', ' ', "\n\t", ' ', 'Trout', ' ', "\n ", ' '));
        $this->assertNotNull($part);
        $this->assertEquals(" Kilgore  \t Trout   ", $part->getValue());
    }
}
