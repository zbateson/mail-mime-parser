<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part\Factory;

use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\MessageFactory;
use ZBateson\MailMimeParser\Message\MessageHelperFactory;
use ZBateson\MailMimeParser\Message\PartFilterFactory;

/**
 * Responsible for creating singleton instances of MessagePartFactory and its
 * subclasses.
 *
 * @author Zaahid Bateson
 */
class PartFactoryService
{
    /**
     * @var HeaderFactory the HeaderFactory object used for created headers
     */
    protected $headerFactory;
    
    /**
     * @var PartFilterFactory the PartFilterFactory instance
     */
    protected $partFilterFactory;
    
    /**
     * @var PartStreamFilterManagerFactory the PartStreamFilterManagerFactory
     *      instance
     */
    protected $partStreamFilterManagerFactory;

    /**
     * @var StreamFactory the StreamFactory instance
     */
    protected $streamFactory;

    /**
     * @var MessageHelperFactory the MessageHelperFactory instance
     */
    protected $messageHelperFactory;
    
    /**
     * @param HeaderFactory $headerFactory
     * @param PartFilterFactory $partFilterFactory
     * @param StreamFactory $streamFactory
     * @param PartStreamFilterManagerFactory $partStreamFilterManagerFactory
     * @param MessageHelperFactory $messageHelperFactory
     */
    public function __construct(
        HeaderFactory $headerFactory,
        PartFilterFactory $partFilterFactory,
        StreamFactory $streamFactory,
        PartStreamFilterManagerFactory $partStreamFilterManagerFactory,
        MessageHelperFactory $messageHelperFactory
    ) {
        $this->headerFactory = $headerFactory;
        $this->partFilterFactory = $partFilterFactory;
        $this->streamFactory = $streamFactory;
        $this->partStreamFilterManagerFactory = $partStreamFilterManagerFactory;
        $this->messageHelperFactory = $messageHelperFactory;
    }

    /**
     * Returns the MessageFactory singleton instance.
     * 
     * @return MessageFactory
     */
    public function getMessageFactory()
    {
        return MessageFactory::getInstance(
            $this->streamFactory,
            $this->partStreamFilterManagerFactory,
            $this->headerFactory,
            $this->partFilterFactory,
            $this->messageHelperFactory->newMessageHelper($this)
        );
    }
    
    /**
     * Returns the MimePartFactory singleton instance.
     * 
     * @return MimePartFactory
     */
    public function getMimePartFactory()
    {
        return MimePartFactory::getInstance(
            $this->streamFactory,
            $this->partStreamFilterManagerFactory,
            $this->headerFactory,
            $this->partFilterFactory
        );
    }
    
    /**
     * Returns the NonMimePartFactory singleton instance.
     * 
     * @return NonMimePartFactory
     */
    public function getNonMimePartFactory()
    {
        return NonMimePartFactory::getInstance(
            $this->streamFactory,
            $this->partStreamFilterManagerFactory
        );
    }
    
    /**
     * Returns the UUEncodedPartFactory singleton instance.
     * 
     * @return UUEncodedPartFactory
     */
    public function getUUEncodedPartFactory()
    {
        return UUEncodedPartFactory::getInstance(
            $this->streamFactory,
            $this->partStreamFilterManagerFactory
        );
    }
}
