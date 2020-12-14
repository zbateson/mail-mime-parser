<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part\Factory;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Message\PartFilterFactory;
use ZBateson\MailMimeParser\Message\Part\MimePart;
use ZBateson\MailMimeParser\Message\Part\PartBuilder;
use ZBateson\MailMimeParser\Message\Part\PartStreamContainer;

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
     * Constructs a new MimePart object and returns it
     * 
     * @param PartBuilder $partBuilder
     * @param StreamInterface $messageStream
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function newInstance(PartBuilder $partBuilder, StreamInterface $messageStream = null)
    {
        $streamContainer = new PartStreamContainer($this->streamFactory);
        if ($messageStream !== null) {
            $streamContainer->setStream($this->streamFactory->getLimitedPartStream($messageStream, $partBuilder));
            $streamContainer->setContentStream($this->streamFactory->getLimitedContentStream($messageStream, $partBuilder));
        }
        $part = new MimePart(
            $this->streamFactory,
            $this->partFilterFactory
        );
        $part->initFrom($partBuilder, $streamContainer);
        return $part;
    }
}
