<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use GuzzleHttp\Psr7;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Message\MessageService;
use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Message\PartChildrenContainer;
use ZBateson\MailMimeParser\Message\PartFilter;
use ZBateson\MailMimeParser\Message\PartStreamContainer;

/**
 * An email message.
 *
 * The message could represent a simple text email, a multipart message with
 * children, or a non-mime message containing UUEncoded parts.
 *
 * @author Zaahid Bateson
 */
class Message extends MimePart implements IMessage
{
    /**
     * @var MessageService helper class with various message manipulation
     *      routines.
     */
    protected $messageService;

    public function __construct(
        PartStreamContainer $streamContainer = null,
        PartHeaderContainer $headerContainer = null,
        PartChildrenContainer $partChildrenContainer = null,
        MessageService $messageService = null
    ) {
        parent::__construct(
            null,
            $streamContainer,
            $headerContainer,
            $partChildrenContainer
        );
        if ($messageService === null) {
            $messageService = $di['\ZBateson\MailMimeParser\Message\MessageService'];
        }
        $this->messageService = $messageService;
    }

    /**
     * Convenience method to parse a handle or string into an IMessage without
     * requiring including MailMimeParser, instantiating it, and calling parse.
     *
     * If the passed $handleOrString is a resource handle, the handle must be
     * kept open while the Message object exists.  For that reason, the default
     * attachment mode is 'attached', which will cause the Message object to
     * close the passed resource handle when it's destroyed.  If the stream
     * should remain open for other reasons and closed manually, pass FALSE as
     * the second parameter so the Message object does not close the stream.
     *
     * @param resource|string $handleOrString the resource handle to the input
     *        stream of the mime message, or a string containing a mime message.
     * @param bool $attached set to false to keep the stream open when the
     *        returned IMessage is destroyed.
     * @return IMessage
     */
    public static function from($handleOrString, $attached = true)
    {
        static $mmp = null;
        if ($mmp === null) {
            $mmp = new MailMimeParser();
        }
        return $mmp->parse($handleOrString, $attached);
    }

    /**
     * {@inheritDoc}
     *
     * The message is considered 'mime' if it has either a Content-Type or
     * Mime-Version header defined.
     *
     * @return bool
     */
    public function isMime()
    {
        $contentType = $this->getHeaderValue('Content-Type');
        $mimeVersion = $this->getHeaderValue('Mime-Version');
        return ($contentType !== null || $mimeVersion !== null);
    }

    public function getTextPart($index = 0)
    {
        return $this->getPart(
            $index,
            PartFilter::fromInlineContentType('text/plain')
        );
    }

    public function getTextPartCount()
    {
        return $this->getPartCount(
            PartFilter::fromInlineContentType('text/plain')
        );
    }

    public function getHtmlPart($index = 0)
    {
        return $this->getPart(
            $index,
            PartFilter::fromInlineContentType('text/html')
        );
    }

    public function getHtmlPartCount()
    {
        return $this->getPartCount(
            PartFilter::fromInlineContentType('text/html')
        );
    }

    public function getTextStream($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $textPart = $this->getTextPart($index);
        if ($textPart !== null) {
            return $textPart->getContentStream($charset);
        }
        return null;
    }

    public function getTextContent($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $part = $this->getTextPart($index);
        if ($part !== null) {
            return $part->getContent($charset);
        }
        return null;
    }

    public function getHtmlStream($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $htmlPart = $this->getHtmlPart($index);
        if ($htmlPart !== null) {
            return $htmlPart->getContentStream($charset);
        }
        return null;
    }

    public function getHtmlContent($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $part = $this->getHtmlPart($index);
        if ($part !== null) {
            return $part->getContent($charset);
        }
        return null;
    }

    public function setTextPart($resource, $charset = 'UTF-8')
    {
        $this->messageService
            ->getMultipartHelper()
            ->setContentPartForMimeType(
                $this, 'text/plain', $resource, $charset
            );
    }

    public function setHtmlPart($resource, $charset = 'UTF-8')
    {
        $this->messageService
            ->getMultipartHelper()
            ->setContentPartForMimeType(
                $this, 'text/html', $resource, $charset
            );
    }

    public function removeTextPart($index = 0)
    {
        return $this->messageService
            ->getMultipartHelper()
            ->removePartByMimeType(
                $this, 'text/plain', $index
            );
    }

    public function removeAllTextParts($moveRelatedPartsBelowMessage = true)
    {
        return $this->messageService
            ->getMultipartHelper()
            ->removeAllContentPartsByMimeType(
                $this, 'text/plain', $moveRelatedPartsBelowMessage
            );
    }

    public function removeHtmlPart($index = 0)
    {
        return $this->messageService
            ->getMultipartHelper()
            ->removePartByMimeType(
                $this, 'text/html', $index
            );
    }

    public function removeAllHtmlParts($moveRelatedPartsBelowMessage = true)
    {
        return $this->messageService
            ->getMultipartHelper()
            ->removeAllContentPartsByMimeType(
                $this, 'text/html', $moveRelatedPartsBelowMessage
            );
    }

    public function getAttachmentPart($index)
    {
        return $this->getPart(
            $index,
            PartFilter::fromAttachmentFilter()
        );
    }

    public function getAllAttachmentParts()
    {
        return $this->getAllParts(
            PartFilter::fromAttachmentFilter()
        );
    }

    public function getAttachmentCount()
    {
        return count($this->getAllAttachmentParts());
    }

    public function addAttachmentPart($resource, $mimeType, $filename = null, $disposition = 'attachment', $encoding = 'base64')
    {
        $this->messageService
            ->getMultipartHelper()
            ->createAndAddPartForAttachment(
                $this,
                $resource,
                $mimeType,
                (strcasecmp($disposition, 'inline') === 0) ? 'inline' : 'attachment',
                $filename,
                $encoding
            );
    }

    public function addAttachmentPartFromFile($filePath, $mimeType, $filename = null, $disposition = 'attachment', $encoding = 'base64')
    {
        $handle = Psr7\stream_for(fopen($filePath, 'r'));
        if ($filename === null) {
            $filename = basename($filePath);
        }
        $this->addAttachmentPart($handle, $mimeType, $filename, $disposition, $encoding);
    }

    public function removeAttachmentPart($index)
    {
        $part = $this->getAttachmentPart($index);
        $this->removePart($part);
    }

    public function getSignedMessageStream()
    {
        return $this
            ->messageService
            ->getPrivacyHelper()
            ->getSignedMessageStream($this);
    }

    public function getSignedMessageAsString()
    {
        return $this
            ->messageService
            ->getPrivacyHelper()
            ->getSignedMessageAsString($this);
    }

    public function getSignaturePart()
    {
        if (strcasecmp($this->getContentType(), 'multipart/signed') === 0) {
            return $this->getChild(1);
        } else {
            return null;
        }
    }

    public function setAsMultipartSigned($micalg, $protocol)
    {
        $this
            ->messageService
            ->getPrivacyHelper()
            ->setMessageAsMultipartSigned($this, $micalg, $protocol);
    }

    public function setSignature($body)
    {
        $this->messageService->getPrivacyHelper()
            ->setSignature($this, $body);
    }
}
