<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Message\Factory\PartFilterFactory;
use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Parser\PartBuilder;

/**
 * Responsible for creating ParsedMimePart instances.
 *
 * @author Zaahid Bateson
 */
class ParsedMimePartFactory extends ParsedMessagePartFactory
{
    /**
     * @var PartFilterFactory an instance used for creating MimePart objects
     */
    protected $partFilterFactory;

    public function __construct(
        StreamFactory $sdf,
        ParsedPartStreamContainerFactory $pscf,
        PartFilterFactory $pf
    ) {
        parent::__construct($sdf, $pscf);
        $this->partFilterFactory = $pf;
    }

    /**
     * Loops through the passed PartBuilder's children, calling
     * createMessagePart on each one and returning an array of IMessageParts
     *
     * @param PartBuilder $partBuilder
     * @param StreamInterface $partStream
     * @return IMessagePart[] children
     */
    protected function buildChildren(PartBuilder $partBuilder, StreamInterface $partStream = null)
    {
        $pbChildren = $partBuilder->getChildren();
        $children = [];
        if (!empty($pbChildren)) {
            $children = array_map(function ($child) use ($partStream) {
                $childPart = $child->createMessagePart(
                    $this->streamFactory->getLimitedPartStream($partStream, $child)
                );
                return $childPart;
            }, $pbChildren);
        }
        return $children;
    }

    /**
     * Constructs a new MimePart object and returns it
     * 
     * @param PartBuilder $partBuilder
     * @param StreamInterface $partStream
     * @return \ZBateson\MailMimeParser\Message\IMimePart
     */
    public function newInstance(PartBuilder $partBuilder, StreamInterface $partStream = null)
    {
        $streamContainer = $this->parsedPartStreamContainerFactory->newInstance();
        if ($partStream !== null) {
            $streamContainer->setContentStream($this->streamFactory->getLimitedContentStream($partStream, $partBuilder));
        }
        $children = $this->buildChildren($partBuilder, $partStream);
        $headerContainer = $partBuilder->getHeaderContainer();
        $part = new MimePart(
            $children,
            $streamContainer,
            $headerContainer,
            $this->partFilterFactory
        );
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        $streamContainer->setParsedStream($partStream);
        $part->attach($streamContainer);
        return $part;
    }
}
