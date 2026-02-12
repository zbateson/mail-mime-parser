<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of ContainerPartTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(ContainerPart::class)]
#[CoversClass(HeaderPart::class)]
#[Group('HeaderParts')]
#[Group('ContainerPart')]
class ContainerPartTest extends TestCase
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

    private function getTokenArray(string ...$args) : array
    {
        return \array_map(
            fn ($a) => $this->getMockBuilder(Token::class)
                ->setConstructorArgs([$this->logger, $this->mb, $a])
                ->onlyMethods([])
                ->getMock(),
            $args
        );
    }

    private function getCommentPart(string $comment) : CommentPart
    {
        return $this->getMockBuilder(CommentPart::class)
            ->setConstructorArgs([$this->logger, $this->mb, $this->hpf, $this->getTokenArray($comment)])
            ->onlyMethods([])
            ->getMock();
    }

    private function newContainerPart($childParts) : ContainerPart
    {
        return new ContainerPart($this->logger, $this->mb, $childParts);
    }

    public function testInstance() : void
    {
        $part = $this->newContainerPart($this->getTokenArray('Kilgore Trout'));
        $this->assertNotNull($part);
        $this->assertEquals('Kilgore Trout', $part->getValue());
    }

    public function testIgnorableSpaces() : void
    {
        $part = $this->newContainerPart($this->getTokenArray(' ', 'Kilgore', ' ', ' ', "\n\t ", 'Trout', ' ', "\n ", ' '));
        $this->assertNotNull($part);
        $this->assertEquals('Kilgore Trout', $part->getValue());
    }

    public function testIgnorableSpacesWithComments() : void
    {
        $part = $this->newContainerPart(\array_merge(
            $this->getTokenArray(' ', 'Kilgore', ' '),
            [$this->getCommentPart('test a comment')],
            $this->getTokenArray(' ', "\n\t ", 'Trout', ' '),
            [$this->getCommentPart('test another comment')],
            $this->getTokenArray(' ')
        ));
        $this->assertNotNull($part);
        $this->assertEquals('Kilgore Trout', $part->getValue());
    }

    public function testGetChildParts() : void
    {
        $children = $this->getTokenArray(' ', 'Kilgore', ' ', ' ', "\n\t ", 'Trout', ' ', "\n ", ' ');
        $part = $this->newContainerPart($children);
        $this->assertNotNull($part);
        $this->assertEquals('Kilgore Trout', $part->getValue());
        $this->assertCount(9, $part->getChildParts());
        $this->assertEquals($children, $part->getChildParts());
    }

    public function testGetChildPartsWithComments() : void
    {
        $children = \array_merge(
            $this->getTokenArray(' ', 'Kilgore', ' '),
            [$this->getCommentPart('test a comment')],
            $this->getTokenArray(' ', "\n\t ", 'Trout', ' '),
            [$this->getCommentPart('test another comment')],
            $this->getTokenArray(' ')
        );
        $part = $this->newContainerPart($children);
        $this->assertNotNull($part);
        $this->assertEquals('Kilgore Trout', $part->getValue());
        $this->assertCount(10, $part->getChildParts());
        $this->assertEquals($children, $part->getChildParts());
        $this->assertCount(2, $part->getComments());
        $this->assertEquals([$children[3], $children[8]], $part->getComments());
    }

    public function testErrorOnChildPart() : void
    {
        $tokens = $this->getTokenArray('Kilgore', ' ');
        $part = $this->newContainerPart([$this->newContainerPart($tokens), $this->getTokenArray(' ')[0], $this->newContainerPart($this->getTokenArray(' ', 'Trout'))]);
        $tokens[0]->addError('Test', \Psr\Log\LogLevel::ERROR);

        $this->assertNotNull($part);
        $this->assertEquals('Kilgore Trout', $part->getValue());

        $errors = $part->getAllErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('Test', $errors[0]->getMessage());
    }
}
