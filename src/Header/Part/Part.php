<?php
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Abstract base class representing a single part of a parsed header.
 *
 * @author Zaahid Bateson
 */
abstract class Part
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
}
