<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of CommentConsumerTest
 *
 * @group Consumers
 * @group CommentConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\CommentConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class CommentConsumerTest extends PHPUnit_Framework_TestCase
{
    private $commentConsumer;
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $cs = new ConsumerService($pf, $mlpf);
        $this->commentConsumer = CommentConsumer::getInstance($cs, $pf);
    }
    
    protected function assertCommentConsumed($expected, $value)
    {
        $ret = $this->commentConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\CommentPart', $ret[0]);
        $this->assertEquals('', $ret[0]->getValue());
        $this->assertEquals($expected, $ret[0]->getComment());
    }
    
    public function testConsumeTokens()
    {
        $comment = 'Some silly comment made about my moustache';
        $this->assertCommentConsumed($comment, $comment);
    }
    
    public function testNestedComments()
    {
        $comment = 'A very silly comment (made about my (very awesome) moustache no less)';
        $this->assertCommentConsumed($comment, $comment);
    }
    
    public function testCommentWithQuotedLiteral()
    {
        $comment = 'A ("very ) wrong") comment was made (about my moustache obviously)';
        $this->assertCommentConsumed($comment, $comment);
    }
    
    public function testMimeEncodedComment()
    {
        $this->assertCommentConsumed(
            'A comment was made (about my moustache obviously)',
            'A comment was made (about my =?ISO-8859-1?Q?moustache?= obviously)'
        );
    }
}
