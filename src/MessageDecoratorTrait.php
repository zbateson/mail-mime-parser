<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Message\MimePartDecoratorTrait;

/**
 * Ferries calls to an IMessagePart.
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
trait MessageDecoratorTrait
{
    use MimePartDecoratorTrait;

    /**
     * @var IMessage The underlying part to wrap.
     */
    protected $part;

    public function __construct(IMessage $part)
    {
        $this->part = $part;
    }

    public function addAttachmentPart($resource, $mimeType, $filename = null, $disposition = 'attachment', $encoding = 'base64')
    {
        $this->part->addAttachmentPart($resource, $mimeType, $filename, $disposition, $encoding);
    }

    public function addAttachmentPartFromFile($filePath, $mimeType, $filename = null, $disposition = 'attachment', $encoding = 'base64')
    {
        $this->part->addAttachmentPartFromFile($filePath, $mimeType, $filename, $disposition, $encoding);
    }

    public function getAllAttachmentParts()
    {
        return $this->part->getAllAttachmentParts();
    }

    public function getAttachmentCount()
    {
        return $this->part->getAttachmentCount();
    }

    public function getAttachmentPart($index)
    {
        return $this->part->getAttachmentPart($index);
    }

    public function getHtmlContent($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        return $this->part->getHtmlContent($index, $charset);
    }

    public function getHtmlPart($index = 0)
    {
        return $this->part->getHtmlPart($index);
    }

    public function getHtmlPartCount()
    {
        return $this->part->getHtmlPartCount();
    }

    public function getHtmlResourceHandle($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        return $this->part->getHtmlResourceHandle($index, $charset);
    }

    public function getHtmlStream($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        return $this->part->getHtmlStream($index, $charset);
    }

    public function getSignaturePart()
    {
        return $this->part->getSignaturePart();
    }

    public function getSignedMessageAsString()
    {
        return $this->part->getSignedMessageAsString();
    }

    public function getSignedMessageStream()
    {
        return $this->part->getSignedMessageStream();
    }

    public function getTextContent($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        return $this->part->getTextContent($index, $charset);
    }

    public function getTextPart($index = 0)
    {
        return $this->part->getTextPart($index);
    }

    public function getTextPartCount()
    {
        return $this->part->getTextPartCount();
    }

    public function getTextResourceHandle($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        return $this->part->getTextResourceHandle($index, $charset);
    }

    public function getTextStream($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        return $this->part->getTextStream($index, $charset);
    }

    public function removeAllHtmlParts($keepOtherPartsAsAttachments = true)
    {
        return $this->part->removeAllHtmlParts($keepOtherPartsAsAttachments);
    }

    public function removeAllTextParts($keepOtherPartsAsAttachments = true)
    {
        return $this->part->removeAllTextParts($keepOtherPartsAsAttachments);
    }

    public function removeAttachmentPart($index)
    {
        return $this->part->removeAttachmentPart($index);
    }

    public function removeHtmlPart($index = 0)
    {
        return $this->part->removeHtmlPart($index);
    }

    public function removeTextPart($index = 0)
    {
        return $this->part->removeTextPart($index);
    }

    public function setAsMultipartSigned($micalg, $protocol)
    {
        return $this->part->setAsMultipartSigned($micalg, $protocol);
    }

    public function setHtmlPart($resource, $charset = 'UTF-8')
    {
        return $this->part->setHtmlPart($resource, $charset);
    }

    public function setSignature($body)
    {
        return $this->part->setSignature($body);
    }

    public function setTextPart($resource, $charset = 'UTF-8')
    {
        return $this->part->setTextPart($resource, $charset);
    }
}
