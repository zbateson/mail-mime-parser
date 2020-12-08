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
    private $charsetConverter;

    protected function setUp()
    {
        $this->charsetConverter = new MbWrapper();
    }

    public function testBasicComment()
    {
        $comment = 'Some silly comment made about my moustache';
        $part = new CommentPart($this->charsetConverter, $comment);
        $this->assertEquals('', $part->getValue());
        $this->assertEquals($comment, $part->getComment());
    }

    public function testMimeEncoding()
    {
        $part = new CommentPart($this->charsetConverter, '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals('', $part->getValue());
        $this->assertEquals('Kilgore Trout', $part->getComment());
    }
}
