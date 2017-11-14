<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

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
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      instance
     */
    protected $headerFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the PartFilterFactory
     *      instance
     */
    protected $partFilterFactory;

    /**
     * Creates a MimePartFactory instance with its dependencies.
     * 
     * @param PartStreamFilterManagerFactory $psf
     * @param HeaderFactory $hf
     * @param PartFilterFactory $pf
     */
    public function __construct(PartStreamFilterManagerFactory $psf, HeaderFactory $hf, PartFilterFactory $pf)
    {
        parent::__construct($psf);
        $this->headerFactory = $hf;
        $this->partFilterFactory = $pf;
    }
    
    /**
     * Returns the singleton instance for the class.
     * 
     * @param PartStreamFilterManagerFactory $psf
     * @param HeaderFactory $hf
     * @param PartFilterFactory $pf
     * @return MimePartFactory
     */
    public static function getInstance(
        PartStreamFilterManagerFactory $psf,
        HeaderFactory $hf = null,
        PartFilterFactory $pf = null
    ) {
        static $instances = [];
        $class = get_called_class();
        if (!isset($instances[$class])) {
            $instances[$class] = new static($psf, $hf, $pf);
        }
        return $instances[$class];
    }

    /**
     * Constructs a new MimePart object and returns it
     * 
     * @param string $messageObjectId
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function newInstance($messageObjectId, PartBuilder $partBuilder)
    {
        return new MimePart(
            $this->headerFactory,
            $this->partFilterFactory,
            $messageObjectId,
            $partBuilder,
            $this->partStreamFilterManagerFactory->newInstance()
        );
    }
}
