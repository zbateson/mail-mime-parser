<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Factory;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Message\Factory\PartFilterFactory;
use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Message\PartStreamContainer;

/**
 * Responsible for creating MimePart instances.
 *
 * @author Zaahid Bateson
 */
class MimePartFactory extends MessagePartFactory
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

    public function __construct(
        StreamFactory $streamFactory,
        PartStreamContainerFactory $partStreamContainerFactory,
        HeaderFactory $headerFactory,
        PartFilterFactory $partFilterFactory
    ) {
        parent::__construct($streamFactory, $partStreamContainerFactory);
        $this->headerFactory = $headerFactory;
        $this->partFilterFactory = $partFilterFactory;
    }

    /**
     * Constructs a new IMimePart object and returns it
     *
     * @return \ZBateson\MailMimeParser\Message\IMimePart
     */
    public function newInstance()
    {
        $streamContainer = $this->partStreamContainerFactory->newInstance();
        $headerContainer = $this->headerFactory->newHeaderContainer();
        $part = new MimePart(
            [],
            $streamContainer,
            $headerContainer,
            $this->partFilterFactory
        );
        $streamContainer->setStream($this->streamFactory->newMessagePartStream($part));
        return $part;
    }
}
