<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of CommentConsumerServiceTest
 *
 * @group Consumers
 * @group CommentConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class CommentConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $commentConsumer;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MbWrapperService::class)
            ->setMethods(['__toString'])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $mlpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $cs = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Consumer\ConsumerService::class)
            ->setConstructorArgs([$pf, $mlpf])
            ->setMethods(['__toString'])
            ->getMock();
        $this->commentConsumer = new CommentConsumerService($cs, $pf);
    }

    public function testIsService() : void
    {
        $this->assertInstanceOf(\ZBateson\MailMimeParser\Container\IService::class, $this->commentConsumer);
    }

    protected function assertCommentConsumed($expected, $value) : void
    {
        $ret = $this->commentConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\CommentPart::class, $ret[0]);
        $this->assertEquals('', $ret[0]->getValue());
        $this->assertEquals($expected, $ret[0]->getComment());
    }

    public function testConsumeTokens() : void
    {
        $comment = 'Some silly comment made about my moustache';
        $this->assertCommentConsumed($comment, $comment);
    }

    public function testNestedComments() : void
    {
        $comment = 'A very silly comment (made about my (very awesome) moustache no less)';
        $this->assertCommentConsumed($comment, $comment);
    }

    public function testCommentWithQuotedLiteral() : void
    {
        $comment = 'A ("very ) wrong") comment was made (about my moustache obviously)';
        $this->assertCommentConsumed($comment, $comment);
    }

    public function testMimeEncodedComment() : void
    {
        $this->assertCommentConsumed(
            'A comment was made (about my moustache obviously)',
            'A comment was made (about my =?ISO-8859-1?Q?moustache?= obviously)'
        );
    }
}