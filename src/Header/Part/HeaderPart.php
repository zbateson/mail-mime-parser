<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Abstract base class representing a single part of a parsed header.
 *
 * @author Zaahid Bateson
 */
abstract class HeaderPart
{
    /**
     * @var string the value of the part
     */
    protected $value;

    /**
     * Returns the part's value.
     * 
     * @return string the value of the part
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Returns the value of the part (which is a string).
     * 
     * @return string the value
     */
    public function __toString()
    {
        return $this->value;
    }
    
    /**
     * Returns true if spaces before this part should be ignored.  True is only
     * returned for MimeLiterals if the part begins with a mime-encoded string,
     * Tokens if the Token's value is a single space, and for CommentParts.
     * 
     * @return bool
     */
    public function ignoreSpacesBefore()
    {
        return false;
    }
    
    /**
     * Returns true if spaces after this part should be ignored.  True is only
     * returned for MimeLiterals if the part ends with a mime-encoded string
     * Tokens if the Token's value is a single space, and for CommentParts.
     * 
     * @return bool
     */
    public function ignoreSpacesAfter()
    {
        return false;
    }
    
    /**
     * Ensures the encoding of the passed string is set to UTF-8.
     * 
     * @param string $str
     * @return string utf-8 string
     */
    protected function convertEncoding($str)
    {
        if (!mb_check_encoding($str, 'UTF-8')) {
            return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
        }
        return $str;
    }
}
