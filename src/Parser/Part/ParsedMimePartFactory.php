<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Message\Factory\PartFilterFactory;
use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Message\MultiPart;
use ZBateson\MailMimeParser\Parser\BaseParser;
use ZBateson\MailMimeParser\Parser\ParserProxy;
use ZBateson\MailMimeParser\Parser\PartBuilder;

/**
 * Responsible for creating ParsedMimePart instances.
 *
 * @author Zaahid Bateson
 */
class ParsedMimePartFactory extends ParsedMessagePartFactory
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      instance
     */
    protected $headerFactory;

    /**
     * @var PartFilterFactory an instance used for creating MimePart objects
     */
    protected $partFilterFactory;

    /**
     * @var ParsedPartChildrenContainerFactory
     */
    protected $parsedPartChildrenContainerFactory;

    public function __construct(
        StreamFactory $sdf,
        HeaderFactory $headerFactory,
        ParsedPartStreamContainerFactory $pscf,
        ParsedPartChildrenContainerFactory $ppccf,
        PartFilterFactory $pf,
        BaseParser $baseParser
    ) {
        parent::__construct($sdf, $pscf, $baseParser);
        $this->headerFactory = $headerFactory;
        $this->partFilterFactory = $pf;
        $this->parsedPartChildrenContainerFactory = $ppccf;
    }

    /**
     * Constructs a new MimePart object and returns it
     * 
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\IMimePart
     */
    public function newInstance(PartBuilder $partBuilder, IMimePart $parent = null)
    {
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance($partBuilder);
        $headerContainer = $this->headerFactory->newHeaderContainer($partBuilder->getHeaderContainer());

        $part = null;
        $childrenContainer = null;
        if ($partBuilder->getMimeBoundary() !== null) {
            $childrenContainer = $this->parsedPartChildrenContainerFactory->newInstance($partBuilder);
            $part = new MultiPart(
                $parent,
                $streamContainer,
                $headerContainer,
                $childrenContainer,
                $this->partFilterFactory
            );
        } else {
            $part = new MimePart(
                $parent,
                $streamContainer,
                $headerContainer
            );
        }

        $partBuilder->setContainers($streamContainer, $childrenContainer);
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $part->attach($streamContainer);
        return $part;
    }
}
