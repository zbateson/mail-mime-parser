<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\MessageService;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Parser\Part\MimePartFactory;
use ZBateson\MailMimeParser\Message\PartStreamContainer;
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
     * Constructs a new IMessage object and returns it
     *
     * @param PartBuilder $partBuilder
     * @param StreamInterface $stream
     * @return \ZBateson\MailMimeParser\Message\IMimePart
     */
    public function newInstance(PartBuilder $partBuilder, StreamInterface $stream = null)
    {
        $streamContainer = new PartStreamContainer($this->streamFactory);
        if ($stream !== null) {
            $streamContainer->setContentStream($this->streamFactory->getLimitedContentStream($stream, $partBuilder));
        }
        $children = $this->buildChildren($partBuilder, $stream);

        $headerContainer = $partBuilder->getHeaderContainer();
        $message = new Message(
            $children,
            $streamContainer,
            $headerContainer,
            $this->partFilterFactory,
            $this->messageService
        );
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($message));
        return new ParsedMessage($message, $stream);
    }
}
