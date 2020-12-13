<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\Helper\GenericHelper;
use ZBateson\MailMimeParser\Message\Helper\MultipartHelper;
use ZBateson\MailMimeParser\Message\Helper\PrivacyHelper;
use ZBateson\MailMimeParser\Message\Part\Factory\MimePartFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\NonMimePartFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\PartBuilderFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\PartStreamFilterManagerFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\UUEncodedPartFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Responsible for creating helper singletons and Message parts.
 *
 * @author Zaahid Bateson
 */
class MessageService
{
    /**
     * @var PartBuilderFactory the PartBuilderFactory
     */
    private $partBuilderFactory;

    /**
     * @var GenericHelper the GenericHelper singleton
     */
    private $genericHelper;

    /**
     * @var MultipartHelper the MultipartHelper singleton
     */
    private $multipartHelper;

    /**
     * @var PrivacyHelper the PrivacyHelper singleton
     */
    private $privacyHelper;

        /**
     * @var PartFilterFactory the PartFilterFactory instance
     */
    protected $partFilterFactory;

    /**
     * @var PartStreamFilterManagerFactory the PartStreamFilterManagerFactory
     *      instance
     */
    protected $partStreamFilterManagerFactory;

    /**
     * @var StreamFactory the StreamFactory instance
     */
    protected $streamFactory;

    /**
     * Constructor
     *
     * @param PartBuilderFactory $partBuilderFactory
     */
    public function __construct(
        PartBuilderFactory $partBuilderFactory,
        PartFilterFactory $partFilterFactory,
        StreamFactory $streamFactory,
        PartStreamFilterManagerFactory $partStreamFilterManagerFactory
    ) {
        $this->partBuilderFactory = $partBuilderFactory;
        $this->partFilterFactory = $partFilterFactory;
        $this->streamFactory = $streamFactory;
        $this->partStreamFilterManagerFactory = $partStreamFilterManagerFactory;
    }

    /**
     * Returns the GenericHelper singleton
     * 
     * @return GenericHelper
     */
    public function getGenericHelper()
    {
        if ($this->genericHelper === null) {
            $this->genericHelper = new GenericHelper(
                $this->getMimePartFactory(),
                $this->getUUEncodedPartFactory(),
                $this->partBuilderFactory
            );
        }
        return $this->genericHelper;
    }

    /**
     * Returns the MultipartHelper singleton
     *
     * @return MultipartHelper
     */
    public function getMultipartHelper()
    {
        if ($this->multipartHelper === null) {
            $this->multipartHelper = new MultipartHelper(
                $this->getMimePartFactory(),
                $this->getUUEncodedPartFactory(),
                $this->partBuilderFactory,
                $this->getGenericHelper()
            );
        }
        return $this->multipartHelper;
    }

    /**
     * Returns the PrivacyHelper singleton
     *
     * @return PrivacyHelper
     */
    public function getPrivacyHelper()
    {
        if ($this->privacyHelper === null) {
            $this->privacyHelper = new PrivacyHelper(
                $this->getMimePartFactory(),
                $this->getUUEncodedPartFactory(),
                $this->partBuilderFactory,
                $this->getGenericHelper(),
                $this->getMultipartHelper()
            );
        }
        return $this->privacyHelper;
    }

    /**
     * Returns the MessageFactory singleton instance.
     *
     * @return MessageFactory
     */
    public function getMessageFactory()
    {
        return MessageFactory::getInstance(
            $this->streamFactory,
            $this->partStreamFilterManagerFactory,
            $this->partFilterFactory,
            $this
        );
    }

    /**
     * Returns the MimePartFactory singleton instance.
     *
     * @return MimePartFactory
     */
    public function getMimePartFactory()
    {
        return MimePartFactory::getInstance(
            $this->streamFactory,
            $this->partStreamFilterManagerFactory,
            $this->partFilterFactory
        );
    }

    /**
     * Returns the NonMimePartFactory singleton instance.
     *
     * @return NonMimePartFactory
     */
    public function getNonMimePartFactory()
    {
        return NonMimePartFactory::getInstance(
            $this->streamFactory,
            $this->partStreamFilterManagerFactory
        );
    }

    /**
     * Returns the UUEncodedPartFactory singleton instance.
     *
     * @return UUEncodedPartFactory
     */
    public function getUUEncodedPartFactory()
    {
        return UUEncodedPartFactory::getInstance(
            $this->streamFactory,
            $this->partStreamFilterManagerFactory
        );
    }
}
