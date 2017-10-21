<?php
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\Writer\MessageWriterService;
use ZBateson\MailMimeParser\Stream\PartStreamRegistry;
use ZBateson\MailMimeParser\Message\Part\MimePart;
use ZBateson\MailMimeParser\Message\Part\MimePartFactory;

/**
 * Description of MessageFactory
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class MessageFactory extends MimePartFactory
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      object used for created headers
     */
    protected $headerFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\Writer\MessageWriterService the part
     *      writer for this Message.  The same object is assigned to $partWriter
     *      but as an AbstractWriter -- not really needed in PHP but helps with
     *      auto-complete and code analyzers.
     */
    protected $messageWriterService;
    
    protected $partStreamRegistry;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\Part\MimePartFactory a MimePartFactory to create
     *      parts for attachments/content
     */
    protected $mimePartFactory;
    
    public function __construct(
        HeaderFactory $headerFactory,   
        MessageWriterService $messageWriterService,
        PartStreamRegistry $partStreamRegistry
    ) {
        $this->headerFactory = $headerFactory;
        $this->messageWriterService = $messageWriterService;
        $this->mimePartFactory = $mimePartFactory;
        $this->partStreamRegistry = $partStreamRegistry;
    }

    public function newInstance(
        $handle,
        MimePart $parent,
        array $children,
        array $headers,
        array $properties
    ) {
        return new MimePart(
            $this->headerFactory,
            $this->messageWriterService->getMimePartWriter(),
            $handle,
            $parent,
            $children,
            $headers
        );
    }
}
