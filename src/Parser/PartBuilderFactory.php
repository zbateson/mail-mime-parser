<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Part\ParsedMessagePartFactory;
use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use Psr\Http\Message\StreamInterface;

/**
 * Responsible for creating PartBuilder instances.
 * 
 * The PartBuilder instance must be constructed with a MessagePartFactory
 * instance to construct a MessagePart sub-class after parsing a message into
 * PartBuilder instances.
 *
 * @author Zaahid Bateson
 */
class PartBuilderFactory
{
    /**
     * @var PartHeaderContainerFactory
     */
    protected $partHeaderContainerFactory;

    /**
     * @var BaseParser
     */
    private $baseParser;

    /**
     * @var StreamFactory
     */
    protected $streamFactory;
    
    public function __construct(
        PartHeaderContainerFactory $partHeaderContainerFactory,
        StreamFactory $streamFactory,
        BaseParser $parser
    ) {
        $this->partHeaderContainerFactory = $partHeaderContainerFactory;
        $this->streamFactory = $streamFactory;
        $this->baseParser = $parser;
    }
    
    /**
     * Constructs a new PartBuilder object and returns it
     * 
     * @param ParsedMessagePartFactory $messagePartFactory
     * @param StreamInterface $messageStream
     * @return PartBuilder
     */
    public function newPartBuilder(
        ParsedMessagePartFactory $messagePartFactory,
        StreamInterface $messageStream
    ) {
        return new PartBuilder(
            $messagePartFactory,
            $this->streamFactory,
            $this->baseParser,
            $this->partHeaderContainerFactory->newInstance(),
            $messageStream
        );
    }

    /**
     * Constructs a new PartBuilder object and returns it
     *
     * @param ParsedMessagePartFactory $messagePartFactory
     * @param PartBuilder $parent
     * @return PartBuilder
     */
    public function newChildPartBuilder(
        ParsedMessagePartFactory $messagePartFactory,
        PartBuilder $parent
    ) {
        return new PartBuilder(
            $messagePartFactory,
            $this->streamFactory,
            $this->baseParser,
            $this->partHeaderContainerFactory->newInstance(),
            null,
            $parent
        );
    }
}
