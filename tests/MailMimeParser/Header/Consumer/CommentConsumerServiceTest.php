<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use Psr\Log\NullLogger;
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
    private $logger;

    protected function setUp() : void
    {
        $this->logger = new NullLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods()
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->setMethods()
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->setMethods()
            ->getMock();
        $this->commentConsumer = new CommentConsumerService($this->logger, $mpf, $qscs);
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
