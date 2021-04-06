<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Message\Factory\PartFilterFactory;
use ZBateson\MailMimeParser\Message\MessageService;
use ZBateson\MailMimeParser\Parser\BaseParser;
use ZBateson\MailMimeParser\Parser\ParserProxy;
use ZBateson\MailMimeParser\Parser\PartBuilder;
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
        HeaderFactory $headerFactory,
        ParsedPartStreamContainerFactory $pscf,
        ParsedPartChildrenContainerFactory $ppccf,
        PartFilterFactory $pf,
        BaseParser $baseParser,
        MessageService $mhs
    ) {
        parent::__construct($sdf, $headerFactory, $pscf, $ppccf, $pf, $baseParser);
        $this->messageService = $mhs;
    }

    /**
     * Constructs a new IMessage object and returns it
     *
     * @param PartBuilder $partBuilder
     * @param StreamInterface $stream
     * @return \ZBateson\MailMimeParser\Message\IMimePart
     */
    public function newInstance(PartBuilder $partBuilder, IMimePart $parent = null)
    {
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance($partBuilder);
        $headerContainer = $this->headerFactory->newHeaderContainer($partBuilder->getHeaderContainer());
        $childrenContainer = $this->parsedPartChildrenContainerFactory->newInstance($partBuilder);

        $message = new Message(
            $streamContainer,
            $headerContainer,
            $this->partFilterFactory,
            $childrenContainer,
            $this->messageService
        );

        $partBuilder->setContainers($streamContainer, $childrenContainer);
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($message));
        $message->attach($streamContainer);
        return $message;
    }
}
