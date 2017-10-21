<?php
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\Writer\MessageWriterService;
use ZBateson\MailMimeParser\Stream\PartStreamRegistry;

/**
 * Description of MessageFactory
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class PartFactories
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
    
    protected $messageFactory;
    protected $mimePartFactory;
    protected $nonMimePartFactory;
    protected $uuEncodedPartFactory;
    
    public function __construct(
        HeaderFactory $headerFactory,   
        MessageWriterService $messageWriterService
    ) {
        $this->headerFactory = $headerFactory;
        $this->messageWriterService = $messageWriterService;
    }

    public function getMessageFactory() {
        if ($this->messageFactory === null) {
            $this->messageFactory = new MessageFactory($this->headerFactory, $this->messageWriterService);
        }
        return $this->messageFactory;
    }
    
    public function getMimePartFactory() {
        if ($this->mimePartFactory === null) {
            $this->mimePartFactory = new MimePartFactory($this->headerFactory, $this->messageWriterService);
        }
        return $this->mimePartFactory;
    }
    
    public function getNonMimePartFactory() {
        if ($this->nonMimePartFactory === null) {
            $this->nonMimePartFactory = new NonMimePartFactory($this->headerFactory, $this->messageWriterService);
        }
        return $this->nonMimePartFactory;
    }
    
    public function getUUEncodedPartFactory() {
        if ($this->uuEncodedPartFactory === null) {
            $this->uuEncodedPartFactory = new UUEncodedPartFactory($this->headerFactory, $this->messageWriterService);
        }
        return $this->uuEncodedPartFactory;
    }
}
