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
use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Message\Factory\PartFilterFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Responsible for creating ParsedMessage instances.
 *
 * @author Zaahid Bateson
 */
class ParsedMessageFactory extends ParsedMimePartFactory
{
    /**
     * @var MessageService helper class for message manipulation routines.
     */
    protected $messageService;

    public function __construct(
        StreamFactory $sdf,
        ParsedPartStreamContainerFactory $pscf,
        PartFilterFactory $pf,
        MessageService $mhs
    ) {
        parent::__construct($sdf, $pscf, $pf);
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
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance();
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
        $streamContainer->setParsedStream($stream);
        $message->attach($streamContainer);
        return $message;
    }
}
