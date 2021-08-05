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
use ZBateson\MailMimeParser\Parser\MimeParserFactory;
use ZBateson\MailMimeParser\Parser\NonMimeParserFactory;
use ZBateson\MailMimeParser\Parser\Part\ParsedPartChildrenContainerFactory;
use ZBateson\MailMimeParser\Parser\Part\ParsedPartStreamContainerFactory;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use Psr\Http\Message\StreamInterface;

/**
 * Responsible for creating ParsedMessage instances.
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
     * @var ParsedPartStreamContainerFactory
     */
    protected $parsedPartStreamContainerFactory;

    /**
     * @var ParsedPartChildrenContainerFactory
     */
    protected $parsedPartChildrenContainerFactory;

    /**
     * @var ZBateson\MailMimeParser\Parser\IParserFactory[]
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
        ParsedPartStreamContainerFactory $pscf,
        ParsedPartChildrenContainerFactory $ppccf,
        MimeParserFactory $mpf,
        NonMimeParserFactory $nmpf,
        MultipartHelper $multipartHelper,
        PrivacyHelper $privacyHelper
    ) {
        $this->streamFactory = $sdf;
        $this->partHeaderContainerFactory = $phcf;
        $this->parsedPartStreamContainerFactory = $pscf;
        $this->parsedPartChildrenContainerFactory = $ppccf;
        $this->parserFactories = [ $mpf, $nmpf ];
        $this->multipartHelper = $multipartHelper;
        $this->privacyHelper = $privacyHelper;
    }

    public function prependMessageParser(IParser $parser)
    {
        array_unshift($this->parsers, $parser);
    }

    protected function getMessageParser(PartHeaderContainer $container)
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
     * @param StreamInterface $stream
     * @return \ZBateson\MailMimeParser\Message\IMimePart
     */
    public function newInstance(PartBuilder $partBuilder, PartHeaderContainer $headerContainer)
    {
        // changes to headers by the user can't affect parsing which could come
        // after a change to headers is made by the user on the Part
        $copied = $this->partHeaderContainerFactory->newInstance($headerContainer);
        $parserProxy = new ParserMessageProxy($copied, $partBuilder, $this->getMessageParser($headerContainer));
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance($parserProxy);
        $childrenContainer = $this->parsedPartChildrenContainerFactory->newInstance($parserProxy);

        $message = new Message(
            $streamContainer,
            $headerContainer,
            $childrenContainer,
            $this->multipartHelper,
            $this->privacyHelper
        );
        $parserProxy->setPart($message);
        $parserProxy->setParsedPartStreamContainer($streamContainer);
        $parserProxy->setParsedPartChildrenContainer($childrenContainer);

        $streamContainer->setStream($this->streamFactory->newMessagePartStream($message));
        $message->attach($streamContainer);
        return $message;
    }

}
