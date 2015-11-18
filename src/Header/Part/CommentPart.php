<?php
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Represents a mime header comment -- text in a structured mime header
 * value existing within parentheses.
 *
 * @author Zaahid Bateson
 */
class CommentPart extends MimeLiteral
{
    /**
     * @var string the contents of the comment
     */
    protected $comment;
    
    /**
     * Constructs a MimeLiteral, decoding the value if it's mime-encoded.
     * 
     * @param string $token
     */
    public function __construct($token)
    {
        parent::__construct($token);
        $this->comment = $this->value;
        $this->value = '';
    }
    
    /**
     * Returns the comment's text.
     * 
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }
}
