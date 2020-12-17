<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Message\PartFilterFactory;
use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Message\PartStreamContainer;

/**
 * Responsible for creating MimePart instances.
 *
 * @author Zaahid Bateson
 */
class MimePartFactory extends MessagePartFactory
{
    /**
     * @var PartFilterFactory an instance used for creating MimePart objects
     */
    protected $partFilterFactory;

    /**
     * Initializes dependencies.
     *
     * @param StreamFactory $sdf
     * @param PartFilterFactory $pf
     */
    public function __construct(
        StreamFactory $sdf,
        PartFilterFactory $pf
    ) {
        parent::__construct($sdf);
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
        $streamContainer = new PartStreamContainer($this->streamFactory);
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
        return new ParsedMimePart($part, $partStream);
    }
}
