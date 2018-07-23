<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\MessageHelper;
use ZBateson\MailMimeParser\Message\Part\PartBuilder;
use ZBateson\MailMimeParser\Message\Part\Factory\MimePartFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\PartStreamFilterManagerFactory;
use ZBateson\MailMimeParser\Message\PartFilterFactory;
use ZBateson\MailMimeParser\SignedMessage;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Responsible for creating Message instances.
 *
 * @author Zaahid Bateson
 */
class MessageFactory extends MimePartFactory
{
    /**
     * @var MessageHelper helper class for message manipulation routines.
     */
    protected $messageHelper;

    /**
     * @param StreamFactory $sdf
     * @param PartStreamFilterManagerFactory $psf
     * @param HeaderFactory $hf
     * @param PartFilterFactory $pf
     * @param MessageHelper $mh
     */
    public function __construct(
        StreamFactory $sdf,
        PartStreamFilterManagerFactory $psf,
        HeaderFactory $hf,
        PartFilterFactory $pf,
        MessageHelper $mh
    ) {
        parent::__construct($sdf, $psf, $hf, $pf);
        $this->messageHelper = $mh;
    }

    /**
     * Constructs a new Message object and returns it
     * 
     * @param StreamInterface $stream
     * @param PartBuilder $partBuilder
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function newInstance(StreamInterface $stream, PartBuilder $partBuilder)
    {
        if (strcasecmp($partBuilder->getContentType(), 'multipart/signed')) {
            return new SignedMessage(
                $this->partStreamFilterManagerFactory->newInstance(),
                $this->streamFactory,
                $this->partFilterFactory,
                $this->headerFactory,
                $partBuilder,
                $this->messageHelper,
                $stream,
                $this->streamFactory->getLimitedContentStream($stream, $partBuilder)
            );
        }
        return new Message(
            $this->partStreamFilterManagerFactory->newInstance(),
            $this->streamFactory,
            $this->partFilterFactory,
            $this->headerFactory,
            $partBuilder,
            $this->messageHelper,
            $stream,
            $this->streamFactory->getLimitedContentStream($stream, $partBuilder)
        );
    }
}
