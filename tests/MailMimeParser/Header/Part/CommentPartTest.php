<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of CommentTest
 *
 * @group HeaderParts
 * @group CommentPart
 * @covers ZBateson\MailMimeParser\Header\Part\CommentPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class CommentPartTest extends PHPUnit_Framework_TestCase
{
    public function testBasicComment()
    {
        $comment = 'Some silly comment made about my moustache';
        $part = new CommentPart($comment);
        $this->assertEquals('', $part->getValue());
        $this->assertEquals($comment, $part->getComment());
    }
    
    public function testMimeEncoding()
    {
        $part = new CommentPart('=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals('', $part->getValue());
        $this->assertEquals('Kilgore Trout', $part->getComment());
    }
}
