<?php

use ZBateson\MailMimeParser\Header\Part\Comment;

/**
 * Description of CommentTest
 *
 * @group HeaderParts
 * @group Comment
 * @author Zaahid Bateson
 */
class CommentTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicComment()
    {
        $comment = 'Some silly comment made about my moustache';
        $part = new Comment($comment);
        $this->assertEquals('', $part->getValue());
        $this->assertEquals($comment, $part->getComment());
    }
    
    public function testMimeEncoding()
    {
        $part = new Comment('=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals('', $part->getValue());
        $this->assertEquals('Kilgore Trout', $part->getComment());
    }
}
