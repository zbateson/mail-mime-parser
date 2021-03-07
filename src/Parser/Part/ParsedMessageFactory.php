<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\Factory\PartFilterFactory;
use ZBateson\MailMimeParser\Message\MessageService;
use ZBateson\MailMimeParser\Parser\BaseParser;
use ZBateson\MailMimeParser\Parser\ParserProxy;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use GuzzleHttp\Psr7\StreamWrapper;

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
        ParsedPartChildrenContainerFactory $ppccf,
        PartFilterFactory $pf,
        BaseParser $baseParser,
        MessageService $mhs
    ) {
        parent::__construct($sdf, $pscf, $ppccf, $pf, $baseParser);
        $this->messageService = $mhs;
    }

    /**
     * Constructs a new IMessage object and returns it
     *
     * @param PartBuilder $partBuilder
     * @param StreamInterface $stream
     * @return \ZBateson\MailMimeParser\Message\IMimePart
     */
    public function newInstance(PartBuilder $partBuilder)
    {
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance();

        $headerContainer = $partBuilder->getHeaderContainer();
        $childrenContainer = $this->parsedPartChildrenContainerFactory->newInstance();

        $message = new Message(
            [],
            $streamContainer,
            $headerContainer,
            $this->partFilterFactory,
            $childrenContainer,
            $this->messageService
        );

        $parserProxy = new ParserProxy($this->baseParser, $this->streamFactory);
        $parserProxy->init($partBuilder, $streamContainer, $childrenContainer);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($message));
        $streamContainer->setParsedStream($partBuilder->getStream());
        $message->attach($streamContainer);
        return $message;
    }
}
