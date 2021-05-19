<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Message\MessageService;
use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Parser\BaseParser;
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
        PartHeaderContainerFactory $hcf,
        ParsedPartStreamContainerFactory $pscf,
        ParsedPartChildrenContainerFactory $ppccf,
        BaseParser $baseParser,
        MessageService $mhs
    ) {
        parent::__construct($sdf, $hcf, $pscf, $ppccf, $baseParser);
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
        $headerContainer = $this->partHeaderContainerFactory->newInstance($partBuilder->getHeaderContainer());
        $childrenContainer = $this->parsedPartChildrenContainerFactory->newInstance($partBuilder);

        $message = new Message(
            $streamContainer,
            $headerContainer,
            $childrenContainer,
            $this->messageService
        );

        $partBuilder->setContainers($streamContainer, $childrenContainer);
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($message));
        $message->attach($streamContainer);
        return $message;
    }
}
