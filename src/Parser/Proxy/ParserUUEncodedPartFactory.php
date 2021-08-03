<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Message\UUEncodedPart;
use ZBateson\MailMimeParser\Parser\IParserFactory;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Parser\Part\ParsedPartStreamContainerFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Responsible for creating ParsedUUEncodedPart instances.
 *
 * @author Zaahid Bateson
 */
class ParserUUEncodedPartFactory
{
    /**
     * @var StreamFactory the StreamFactory instance
     */
    protected $streamFactory;

    /**
     * @var ParsedPartStreamContainerFactory
     */
    protected $parsedPartStreamContainerFactory;

    /**
     * @var IParserFactory
     */
    protected $parserFactory;

    public function __construct(
        StreamFactory $sdf,
        ParsedPartStreamContainerFactory $parsedPartStreamContainerFactory
    ) {
        $this->streamFactory = $sdf;
        $this->parsedPartStreamContainerFactory = $parsedPartStreamContainerFactory;
    }

    public function setParserFactory(IParserFactory $parserFactory)
    {
        $this->parserFactory = $parserFactory;
    }

    /**
     * Constructs a new IUUEncodedPart object and returns it
     * 
     * @param PartBuilder $partBuilder
     * @return IUUEncodedPart
     */
    public function newInstance(PartBuilder $partBuilder, $mode, $filename, ParserMimePartProxy $parent)
    {
        $parserProxy = new ParserUUEncodedPartProxy($partBuilder, $parent->getParser(), $parent);
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance($parserProxy);

        $part = new UUEncodedPart(
            $mode,
            $filename,
            $parent->getPart(),
            $streamContainer
        );
        $parserProxy->setPart($part);
        $parserProxy->setParsedPartStreamContainer($streamContainer);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $part->attach($streamContainer);

        $parent->addChild($parserProxy);
        return $part;
    }
}
