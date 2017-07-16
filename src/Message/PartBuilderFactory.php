<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\Writer\MessageWriterService;

/**
 * Description of MimePartFactory
 *
 * @author Zaahid Bateson
 */
class PartBuilderFactory
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      instance
     */
    protected $headerFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\Writer\MessageWriterService the
     * MessageWriterService responsible for returning writers
     */
    protected $messageWriterService;
    
    /**
     * Creates a MimePartFactory instance with its dependencies.
     * 
     * @param HeaderFactory $headerFactory
     * @param MessageWriterService $messageWriterService
     */
    public function __construct(HeaderFactory $headerFactory, MessageWriterService $messageWriterService)
    {
        $this->headerFactory = $headerFactory;
        $this->messageWriterService = $messageWriterService;
    }
    
    /**
     * Constructs a new MimePart object and returns it
     * 
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function newPartBuilder()
    {
        return new PartBuilder(
            $this->headerFactory,
            $this->messageWriterService->getMimePartWriter()
        );
    }
}
