<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of ParameterTest
 *
 * @group HeaderParts
 * @group NameValuePart
 * @covers ZBateson\MailMimeParser\Header\Part\NameValuePart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class NameValuePartTest extends TestCase
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
                ->setConstructorArgs([$this->logger, $this->mb, $a])
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

    private function getContainerPart(string $value) : ContainerPart
    {
        return $this->getMockBuilder(ContainerPart::class)
            ->setConstructorArgs([$this->logger, $this->mb, [$this->getToken($value)]])
            ->setMethods()
            ->getMock();
    }

    private function assertNameValue($expectedName, $expectedValue, ?array $actualNameArray = null, ?array $actualValueArray = null) : NameValuePart
    {
        if ($actualNameArray === null) {
            $actualNameArray = $this->getTokenArray($expectedName);
        }
        if ($actualValueArray === null) {
            $actualValueArray = $this->getTokenArray($expectedValue);
        }
        $part = new NameValuePart(
            $this->logger,
            $this->mb,
            $actualNameArray,
            $actualValueArray
        );
        $this->assertEquals($expectedName, $part->getName());
        $this->assertEquals($expectedValue, $part->getValue());
        return $part;
    }

    public function testBasicNameValuePair() : void
    {
        $this->assertNameValue('Name', 'Value');
    }

    public function testIgnorableSpaces() : void
    {
        $parts = $this->getTokenArray(' ', 'Kilgore', ' ' , ' ', "\n\t ", 'Trout', ' ', "\n ", ' ');
        $this->assertNameValue('Kilgore Trout', 'Kilgore Trout', $parts, $parts);
    }

    public function testIgnorableSpacesWithComments() : void
    {
        $parts = \array_merge(
            $this->getTokenArray(' ', 'Kilgore', ' '),
            [$this->getCommentPart('test a comment')],
            $this->getTokenArray(' ', "\n\t ", 'Trout', ' '),
            [$this->getCommentPart('test another comment')],
            $this->getTokenArray(' ')
        );
        $this->assertNameValue('Kilgore Trout', 'Kilgore Trout', $parts, $parts);
    }

    public function testGetChildParts() : void
    {
        $parts = $this->getTokenArray(' ', 'Kilgore', ' ' , ' ', "\n\t ", 'Trout', ' ', "\n ", ' ');
        $part = $this->assertNameValue('Kilgore Trout', 'Kilgore Trout', $parts, $parts);
        $children = \array_merge($parts, $parts);
        $this->assertCount(count($children), $part->getChildParts());
        $this->assertEquals($children, $part->getChildParts());
    }

    public function testGetChildPartsWithComments() : void
    {
        $parts = \array_merge(
            $this->getTokenArray(' ', 'Kilgore', ' '),
            [$this->getCommentPart('test a comment')],
            $this->getTokenArray(' ', "\n\t ", 'Trout', ' '),
            [$this->getCommentPart('test another comment')],
            $this->getTokenArray(' ')
        );
        $part = $this->assertNameValue('Kilgore Trout', 'Kilgore Trout', $parts, $parts);
        $children = \array_merge($parts, $parts);
        $this->assertCount(count($children), $part->getChildParts());
        $this->assertEquals($children, $part->getChildParts());
        $this->assertCount(4, $part->getComments());
        $this->assertEquals([$children[3], $children[8], $children[3], $children[8]], $part->getComments());
    }

    public function testErrorOnChildPart() : void
    {
        $parts = $this->getTokenArray(' ', 'Kilgore', ' ' , ' ', "\n\t ", 'Trout', ' ', "\n ", ' ');
        $part = $this->assertNameValue('Kilgore Trout', 'Kilgore Trout', $parts, $parts);
        $parts[0]->addError('Test', \Psr\Log\LogLevel::ERROR);

        $errors = $part->getAllErrors();
        $this->assertCount(2, $errors);
        $this->assertEquals('Test', $errors[0]->getMessage());
    }
}
