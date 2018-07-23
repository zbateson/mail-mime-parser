<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\SignedMessage;
use ZBateson\MailMimeParser\Message\Part\PartBuilder;
use ZBateson\MailMimeParser\Message\Part\Factory\MimePartFactory;

/**
 * Responsible for creating Message instances.
 *
 * @author Zaahid Bateson
 */
class MessageFactory extends MimePartFactory
{
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
                $this->headerFactory,
                $this->partFilterFactory,
                $partBuilder,
                $this->partStreamFilterManagerFactory->newInstance(),
                $stream,
                $this->streamFactory->getLimitedContentStream($stream, $partBuilder)
            );
        }
        return new Message(
            $this->headerFactory,
            $this->partFilterFactory,
            $partBuilder,
            $this->partStreamFilterManagerFactory->newInstance(),
            $stream,
            $this->streamFactory->getLimitedContentStream($stream, $partBuilder)
        );
    }
}
