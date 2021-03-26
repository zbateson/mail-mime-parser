<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Parser\Part\ParsedPartStreamContainer;

/**
 * Description of ParserProxy
 *
 * @author Zaahid Bateson
 */
class ParserProxy
{
    /**
     * @var BaseParser
     */
    protected $baseParser;

    /**
     * @var StreamFactory
     */
    protected $streamFactory;

    /**
     * @var ParsedPartStreamContainer
     */
    protected $partStreamContainer;

    /**
     * @var IMimePart
     */
    protected $part;

    protected $partBuilder;

    public function __construct(BaseParser $baseParser, StreamFactory $streamFactory)
    {
        $this->baseParser = $baseParser;
        $this->streamFactory = $streamFactory;
    }

    public function init(
        PartBuilder $partBuilder,
        ParsedPartStreamContainer $streamContainer,
        IMimePart $part = null
    ) {
        $this->partStreamContainer = $streamContainer;
        $this->partBuilder = $partBuilder;
        $this->part = $part;
        $this->partStreamContainer->setProxyParser($this);
    }

    public function readContent()
    {
        $this->baseParser->parseContent($this->partBuilder, $this);
    }

    public function updatePartContent(PartBuilder $partBuilder)
    {
        $this->partStreamContainer->setParsedContentStream(
            $this->streamFactory->getLimitedContentStream(
                $partBuilder->getStream(),
                $partBuilder
            )
        );
    }

    /**
     * Returns true if all child parts have been parsed
     * @return type
     */
    public function readNextChild()
    {
        return $this->baseParser->parseNextChild($this->partBuilder, $this);
    }

    public function updatePartChildren(PartBuilder $child)
    {
        $child->createMessagePart($this->part);
    }
}
