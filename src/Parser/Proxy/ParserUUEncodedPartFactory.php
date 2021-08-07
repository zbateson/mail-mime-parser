<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Message\UUEncodedPart;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Responsible for creating proxied IUUEncodedPart instances wrapped in a
 * ParserPartProxy.
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

    public function __construct(
        StreamFactory $sdf,
        ParserPartStreamContainerFactory $parserPartStreamContainerFactory
    ) {
        $this->streamFactory = $sdf;
        $this->parserPartStreamContainerFactory = $parserPartStreamContainerFactory;
    }

    /**
     * Constructs a new ParserPartProxy wrapping an IUUEncoded object.
     * 
     * @param PartBuilder $partBuilder
     * @return ParserPartProxy
     */
    public function newInstance(PartBuilder $partBuilder, $mode, $filename, ParserMimePartProxy $parent)
    {
        $parserProxy = new ParserPartProxy($partBuilder, null, $parent);
        $streamContainer = $this->parserPartStreamContainerFactory->newInstance($parserProxy);

        $part = new UUEncodedPart(
            $mode,
            $filename,
            $parent->getPart(),
            $streamContainer
        );
        $parserProxy->setPart($part);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $part->attach($streamContainer);
        return $parserProxy;
    }
}
