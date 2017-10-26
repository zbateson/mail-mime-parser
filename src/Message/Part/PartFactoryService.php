<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\MessageFactory;

/**
 * Responsible for creating singleton instances of MessagePartFactory and its
 * subclasses.
 *
 * @author Zaahid Bateson
 */
class PartFactoryService
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      object used for created headers
     */
    protected $headerFactory;
    
    /**
     * Sets up dependencies.
     * 
     * @param HeaderFactory $headerFactory
     */
    public function __construct(
        HeaderFactory $headerFactory
    ) {
        $this->headerFactory = $headerFactory;
    }

    /**
     * Returns the MessageFactory singleton instance.
     * 
     * @return MessageFactory
     */
    public function getMessageFactory()
    {
        return MessageFactory::getInstance($this->headerFactory);
    }
    
    /**
     * Returns the MimePartFactory singleton instance.
     * 
     * @return MimePartFactory
     */
    public function getMimePartFactory()
    {
        return MimePartFactory::getInstance($this->headerFactory);
    }
    
    /**
     * Returns the NonMimePartFactory singleton instance.
     * 
     * @return NonMimePartFactory
     */
    public function getNonMimePartFactory()
    {
        return NonMimePartFactory::getInstance();
    }
    
    /**
     * Returns the UUEncodedPartFactory singleton instance.
     * 
     * @return UUEncodedPartFactory
     */
    public function getUUEncodedPartFactory()
    {
        return UUEncodedPartFactory::getInstance();
    }
}
