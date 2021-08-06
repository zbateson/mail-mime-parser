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
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory;
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
     * @var ParserPartStreamContainerFactory
     */
    protected $parserPartStreamContainerFactory;

    /**
     * @var IParserFactory
     */
    protected $parserFactory;

    public function __construct(
        StreamFactory $sdf,
        ParserPartStreamContainerFactory $parserPartStreamContainerFactory
    ) {
        $this->streamFactory = $sdf;
        $this->parserPartStreamContainerFactory = $parserPartStreamContainerFactory;
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
        $streamContainer = $this->parserPartStreamContainerFactory->newInstance($parserProxy);

        $part = new UUEncodedPart(
            $mode,
            $filename,
            $parent->getPart(),
            $streamContainer
        );
        $parserProxy->setPart($part);
        $parserProxy->setParserPartStreamContainer($streamContainer);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $part->attach($streamContainer);

        $parent->addChild($parserProxy);
        return $part;
    }
}
