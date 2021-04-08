<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Message\MultiPart;
use ZBateson\MailMimeParser\Message\Factory\HeaderContainerFactory;
use ZBateson\MailMimeParser\Message\Factory\PartChildrenContainerFactory;
use ZBateson\MailMimeParser\Parser\BaseParser;
use ZBateson\MailMimeParser\Parser\PartBuilder;

/**
 * Responsible for creating ParsedMimePart instances.
 *
 * @author Zaahid Bateson
 */
class ParsedMimePartFactory extends ParsedMessagePartFactory
{
    /**
     * @var HeaderContainerFactory
     */
    protected $headerContainerFactory;

    /**
     * @var PartChildrenContainerFactory
     */
    protected $partChildrenContainerFactory;

    /**
     * @var ParsedPartChildrenContainerFactory
     */
    protected $parsedPartChildrenContainerFactory;

    public function __construct(
        StreamFactory $sdf,
        HeaderContainerFactory $headerContainerFactory,
        ParsedPartStreamContainerFactory $pscf,
        ParsedPartChildrenContainerFactory $ppccf,
        BaseParser $baseParser
    ) {
        parent::__construct($sdf, $pscf, $baseParser);
        $this->headerContainerFactory = $headerContainerFactory;
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
        $headerContainer = $this->headerContainerFactory->newInstance($partBuilder->getHeaderContainer());

        $childrenContainer = $this->parsedPartChildrenContainerFactory->newInstance($partBuilder);
        $part = new MimePart(
            $parent,
            $streamContainer,
            $headerContainer,
            $childrenContainer
        );

        $partBuilder->setContainers($streamContainer, $childrenContainer);
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $part->attach($streamContainer);
        return $part;
    }
}
