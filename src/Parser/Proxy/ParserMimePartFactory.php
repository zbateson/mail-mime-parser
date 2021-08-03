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
use ZBateson\MailMimeParser\Parser\Part\ParsedPartStreamContainerFactory;
use ZBateson\MailMimeParser\Parser\Part\ParsedPartChildrenContainerFactory;

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
     * @var ParsedPartStreamContainerFactory
     */
    protected $parsedPartStreamContainerFactory;

    /**
     * @var PartHeaderContainerFactory
     */
    protected $partHeaderContainerFactory;

    /**
     * @var PartChildrenContainerFactory
     */
    protected $partChildrenContainerFactory;

    /**
     * @var ParsedPartChildrenContainerFactory
     */
    protected $parsedPartChildrenContainerFactory;

    /**
     * @var IParserFactory
     */
    protected $parserFactory;

    public function __construct(
        StreamFactory $sdf,
        PartHeaderContainerFactory $phcf,
        ParsedPartStreamContainerFactory $pscf,
        ParsedPartChildrenContainerFactory $ppccf
    ) {
        $this->streamFactory = $sdf;
        $this->partHeaderContainerFactory = $phcf;
        $this->parsedPartStreamContainerFactory = $pscf;
        $this->parsedPartChildrenContainerFactory = $ppccf;
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
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance($parserProxy);
        $childrenContainer = $this->parsedPartChildrenContainerFactory->newInstance($parserProxy);

        $part = new MimePart(
            $parent->getPart(),
            $streamContainer,
            $headerContainer,
            $childrenContainer
        );
        $parserProxy->setPart($part);
        $parserProxy->setParsedPartStreamContainer($streamContainer);
        $parserProxy->setParsedPartChildrenContainer($childrenContainer);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $part->attach($streamContainer);

        $parent->addChild($parserProxy);
        return $part;
    }
}
