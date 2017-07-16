<?php
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\Writer\MessageWriterService;
use ZBateson\MailMimeParser\Stream\PartStreamRegistry;

/**
 * Description of MessageFactory
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class MessageFactory
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
     * @var \ZBateson\MailMimeParser\Message\MimePartFactory a MimePartFactory to create
     *      parts for attachments/content
     */
    protected $mimePartFactory;
    
    public function __construct(
        HeaderFactory $headerFactory,   
        MessageWriterService $messageWriterService,
        MimePartFactory $mimePartFactory,
        PartStreamRegistry $partStreamRegistry
    ) {
        $this->headerFactory = $headerFactory;
        $this->messageWriterService = $messageWriterService;
        $this->mimePartFactory = $mimePartFactory;
        $this->partStreamRegistry = $partStreamRegistry;
    }
    
    public function newParsedMessage(PartBuilder $builder, $handle)
    {
        $message = new Message(
            $this->headerFactory,
            $this->messageWriterService->getMessageWriter(),
            $this->mimePartFactory,
            $builder->getHeaders(),
            $builder->getChildParts()
        );
        $this->partStreamRegistry->register($message->getObjectId(), $handle);
        
        foreach ($message->getAllParts() as $key => $part) {
            $bounds = $part;
            if ($bounds === $message) {
                $bounds = $builder;
            }
            
            $this->partStreamRegistry->attachContentPartStreamHandle(
                $part,
                $message,
                $bounds->streamContentReadStartPos,
                $bounds->streamContentReadEndPos
            );
            $this->partStreamRegistry->attachOriginalPartStreamHandle(
                $part,
                $message,
                $bounds->streamPartReadStartPos,
                $bounds->streamPartReadEndPos
            );
        }
        return $message;
    }
}
