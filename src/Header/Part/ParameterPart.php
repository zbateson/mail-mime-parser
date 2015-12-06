<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Represents a name/value pair part of a header.
 * 
 * @author Zaahid Bateson
 */
class ParameterPart extends MimeLiteralPart
{
    /**
     * @var string the name of the parameter
     */
    protected $name;
    
    /**
     * Constructs a ParameterPart out of a name/value pair.  The name and
     * value are both mime-decoded if necessary.
     * 
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value)
    {
        parent::__construct(trim($value));
        $this->name = $this->decodeMime(trim($name));
    }
    
    /**
     * Returns the name of the parameter.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
