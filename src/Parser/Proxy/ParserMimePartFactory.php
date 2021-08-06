<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Parser\IParserFactory;
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory;
use ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainerFactory;

/**
 * Responsible for creating ParsedMimePart instances.
 *
 * @author Zaahid Bateson
 */
class ParserMimePartFactory
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
     * @var PartHeaderContainerFactory
     */
    protected $partHeaderContainerFactory;

    /**
     * @var PartChildrenContainerFactory
     */
    protected $partChildrenContainerFactory;

    /**
     * @var ParserPartChildrenContainerFactory
     */
    protected $parserPartChildrenContainerFactory;

    /**
     * @var IParserFactory
     */
    protected $parserFactory;

    public function __construct(
        StreamFactory $sdf,
        PartHeaderContainerFactory $phcf,
        ParserPartStreamContainerFactory $pscf,
        ParserPartChildrenContainerFactory $ppccf
    ) {
        $this->streamFactory = $sdf;
        $this->partHeaderContainerFactory = $phcf;
        $this->parserPartStreamContainerFactory = $pscf;
        $this->parserPartChildrenContainerFactory = $ppccf;
    }

    public function setParserFactory(IParserFactory $parserFactory)
    {
        $this->parserFactory = $parserFactory;
    }

    /**
     *
     * @param PartBuilder $partBuilder
     * @param PartHeaderContainer $headerContainer
     * @param ParserMimePartProxy $parent
     * @return \ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy
     */
    public function newParserMimePartProxy(PartBuilder $partBuilder, PartHeaderContainer $headerContainer, ParserMimePartProxy $parent)
    {
        return new ParserMimePartProxy($headerContainer, $partBuilder, $this->parserFactory->newInstance(), $parent);
    }

    /**
     * Constructs a new MimePart object and returns it
     *
     * @param PartBuilder $partBuilder
     * @param PartHeaderContainer $headerContainer
     * @param ParserMimePartProxy $parent
     * @return \ZBateson\MailMimeParser\Message\IMimePart
     */
    public function newInstance(PartBuilder $partBuilder, PartHeaderContainer $headerContainer, ParserMimePartProxy $parent)
    {
        // changes to headers by the user can't affect parsing which could come
        // after a change to headers is made by the user on the Part
        $copied = $this->partHeaderContainerFactory->newInstance($headerContainer);
        $parserProxy = $this->newParserMimePartProxy($partBuilder, $copied, $parent);
        $streamContainer = $this->parserPartStreamContainerFactory->newInstance($parserProxy);
        $childrenContainer = $this->parserPartChildrenContainerFactory->newInstance($parserProxy);

        $part = new MimePart(
            $parent->getPart(),
            $streamContainer,
            $headerContainer,
            $childrenContainer
        );
        $parserProxy->setPart($part);
        $parserProxy->setParserPartStreamContainer($streamContainer);
        $parserProxy->setParserPartChildrenContainer($childrenContainer);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $part->attach($streamContainer);

        $parent->addChild($parserProxy);
        return $part;
    }
}
