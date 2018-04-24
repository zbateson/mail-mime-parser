<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Stream\StreamDecoratorFactory;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\PartFilterFactory;

/**
 * Responsible for creating MimePart instances.
 *
 * @author Zaahid Bateson
 */
class MimePartFactory extends MessagePartFactory
{
    /**
     * @var HeaderFactory an instance used for creating MimePart objects 
     */
    protected $headerFactory;

    /**
     * @var PartFilterFactory an instance used for creating MimePart objects
     */
    protected $partFilterFactory;

    /**
     * Initializes dependencies.
     *
     * @param StreamDecoratorFactory $sdf
     * @param PartStreamFilterManagerFactory $psf
     * @param HeaderFactory $hf
     * @param PartFilterFactory $pf
     */
    public function __construct(
        StreamDecoratorFactory $sdf,
        PartStreamFilterManagerFactory $psf,
        HeaderFactory $hf,
        PartFilterFactory $pf
    ) {
        parent::__construct($sdf, $psf);
        $this->headerFactory = $hf;
        $this->partFilterFactory = $pf;
    }

    /**
     * Returns the singleton instance for the class.
     *
     * @param StreamDecoratorFactory $sdf
     * @param PartStreamFilterManagerFactory $psf
     * @param HeaderFactory $hf
     * @param PartFilterFactory $pf
     * @return MessagePartFactory
     */
    public static function getInstance(
        StreamDecoratorFactory $sdf,
        PartStreamFilterManagerFactory $psf,
        HeaderFactory $hf = null,
        PartFilterFactory $pf = null
    ) {
        $instance = static::getCachedInstance();
        if ($instance === null) {
            $instance = new static($sdf, $psf, $hf, $pf);
            static::setCachedInstance($instance);
        }
        return $instance;
    }

    /**
     * Constructs a new MimePart object and returns it
     * 
     * @param StreamInterface $messageStream
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function newInstance(StreamInterface $messageStream, PartBuilder $partBuilder)
    {
        return new MimePart(
            $this->headerFactory,
            $this->partFilterFactory,
            $partBuilder,
            $this->partStreamFilterManagerFactory->newInstance(),
            $this->streamDecoratorFactory->getLimitedPartStream($messageStream, $partBuilder),
            $this->streamDecoratorFactory->getLimitedContentStream($messageStream, $partBuilder)
        );
    }
}
