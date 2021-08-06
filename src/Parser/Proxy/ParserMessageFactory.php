<?php

/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Message\Helper\MultipartHelper;
use ZBateson\MailMimeParser\Message\Helper\PrivacyHelper;
use ZBateson\MailMimeParser\Parser\IParserFactory;
use ZBateson\MailMimeParser\Parser\MimeParserFactory;
use ZBateson\MailMimeParser\Parser\NonMimeParserFactory;
use ZBateson\MailMimeParser\Parser\Part\ParserPartChildrenContainerFactory;
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use Psr\Http\Message\StreamInterface;

/**
 * Responsible for creating proxied IMessage instances.
 *
 * @author Zaahid Bateson
 */
class ParserMessageFactory
{
    /**
     * @var StreamFactory the StreamFactory instance
     */
    protected $streamFactory;

    /**
     * @var PartHeaderContainerFactory
     */
    protected $partHeaderContainerFactory;

    /**
     * @var ParserPartStreamContainerFactory
     */
    protected $parserPartStreamContainerFactory;

    /**
     * @var ParserPartChildrenContainerFactory
     */
    protected $parserPartChildrenContainerFactory;

    /**
     * @var IParserFactory[]
     */
    protected $parserFactories;

    /**
     * @var MultipartHelper
     */
    private $multipartHelper;

    /**
     * @var PrivacyHelper
     */
    private $privacyHelper;

    public function __construct(
        StreamFactory $sdf,
        PartHeaderContainerFactory $phcf,
        ParserPartStreamContainerFactory $pscf,
        ParserPartChildrenContainerFactory $ppccf,
        MimeParserFactory $mpf,
        NonMimeParserFactory $nmpf,
        MultipartHelper $multipartHelper,
        PrivacyHelper $privacyHelper
    ) {
        $this->streamFactory = $sdf;
        $this->partHeaderContainerFactory = $phcf;
        $this->parserPartStreamContainerFactory = $pscf;
        $this->parserPartChildrenContainerFactory = $ppccf;
        $this->parserFactories = [ $mpf, $nmpf ];
        $this->multipartHelper = $multipartHelper;
        $this->privacyHelper = $privacyHelper;
    }

    /**
     * Adds an IParserFactory at the highest priority (up front).
     *
     * @param IParserFactory $pf
     */
    public function prependMessageParserFactory(IParserFactory $pf)
    {
        array_unshift($this->parserFactories, $pf);
    }

    /**
     * Loops through registered IParserFactories and returns a parser that can
     * parse a part for the passed headers.
     *
     * @param PartHeaderContainer $container
     * @return IParser
     */
    protected function getIParser(PartHeaderContainer $container)
    {
        foreach ($this->parserFactories as $pf) {
            if ($pf->canParse($container)) {
                return $pf->newInstance();
            }
        }
        return null;
    }

    /**
     * Constructs a new IMessage object and returns it
     *
     * @param PartBuilder $partBuilder
     * @param PartHeaderContainer $headerContainer
     * @return \ZBateson\MailMimeParser\Message\IMimePart
     */
    public function newInstance(PartBuilder $partBuilder, PartHeaderContainer $headerContainer)
    {
        // changes to headers by the user can't affect parsing which could come
        // after a change to headers is made by the user on the Part
        $copied = $this->partHeaderContainerFactory->newInstance($headerContainer);
        $parserProxy = new ParserMimePartProxy($copied, $partBuilder, $this->getIParser($headerContainer));
        $streamContainer = $this->parserPartStreamContainerFactory->newInstance($parserProxy);
        $childrenContainer = $this->parserPartChildrenContainerFactory->newInstance($parserProxy);

        $message = new Message(
            $streamContainer,
            $headerContainer,
            $childrenContainer,
            $this->multipartHelper,
            $this->privacyHelper
        );
        $parserProxy->setPart($message);
        $parserProxy->setParserPartStreamContainer($streamContainer);
        $parserProxy->setParserPartChildrenContainer($childrenContainer);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($message));
        $message->attach($streamContainer);
        return $message;
    }

}
