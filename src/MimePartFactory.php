<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;

/**
 * Description of MimePartFactory
 *
 * @author Zaahid Bateson
 */
class MimePartFactory
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      instance
     */
    protected $headerFactory;
    
    /**
     * Creates a MimePartFactory instance with its dependencies.
     * 
     * @param HeaderFactory $headerFactory
     */
    public function __construct(HeaderFactory $headerFactory)
    {
        $this->headerFactory = $headerFactory;
    }
    
    /**
     * Constructs a new MimePart object and returns it
     * 
     * @return \ZBateson\MailMimeParser\MimePart
     */
    public function newMimePart()
    {
        return new MimePart($this->headerFactory);
    }
}
