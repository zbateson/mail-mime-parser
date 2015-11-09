<?php
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;

/**
 * Description of PartFactory
 *
 * @author Zaahid Bateson
 */
class PartFactory
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      instance
     */
    protected $headerFactory;
    
    /**
     * Creates a PartFactory instance with its dependencies.
     * 
     * @param HeaderFactory $headerFactory
     */
    public function __construct(HeaderFactory $headerFactory)
    {
        $this->headerFactory = $headerFactory;
    }
    
    /**
     * Constructs a new Part object and returns it
     * 
     * @return \ZBateson\MailMimeParser\Part
     */
    public function newPart()
    {
        return new Part($this->headerFactory);
    }
}
