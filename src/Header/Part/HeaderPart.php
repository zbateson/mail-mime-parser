<?php
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
     * returned for MimeLiterals if the part begins with a mime-encoded string.
     * 
     * @return bool
     */
    public function ignoreSpacesBefore()
    {
        return false;
    }
    
    /**
     * Returns true if spaces after this part should be ignored.  True is only
     * returned for MimeLiterals if the part ends with a mime-encoded string.
     * 
     * @return bool
     */
    public function ignoreSpacesAfter()
    {
        return false;
    }
}
