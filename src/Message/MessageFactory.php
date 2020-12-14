<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\MessageService;
use ZBateson\MailMimeParser\Message\Part\PartBuilder;
use ZBateson\MailMimeParser\Message\Part\Factory\MimePartFactory;
use ZBateson\MailMimeParser\Message\Part\PartStreamContainer;
use ZBateson\MailMimeParser\Message\PartFilterFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Responsible for creating Message instances.
 *
 * @author Zaahid Bateson
 */
class MessageFactory extends MimePartFactory
{
    /**
     * @var MessageService helper class for message manipulation routines.
     */
    protected $messageService;

    /**
     * Constructor
     * 
     * @param StreamFactory $sdf
     * @param PartFilterFactory $pf
     * @param MessageService $mhs
     */
    public function __construct(
        StreamFactory $sdf,
        PartFilterFactory $pf,
        MessageService $mhs
    ) {
        parent::__construct($sdf, $pf);
        $this->messageService = $mhs;
    }

    /**
     * Constructs a new Message object and returns it
     *
     * @param PartBuilder $partBuilder
     * @param StreamInterface $stream
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function newInstance(PartBuilder $partBuilder, StreamInterface $stream = null)
    {
        $streamContainer = new PartStreamContainer($this->streamFactory);
        if ($stream !== null) {
            $streamContainer->setStream($this->streamFactory->newNonClosingStream($stream));
            $streamContainer->setContentStream($this->streamFactory->getLimitedContentStream($stream, $partBuilder));
        }
        $message = new Message(
            $this->streamFactory,
            $this->partFilterFactory,
            $this->messageService
        );
        $message->initMessageFrom($partBuilder, $stream, $streamContainer);
        return $message;
    }
}
