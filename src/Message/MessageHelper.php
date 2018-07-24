<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use GuzzleHttp\Psr7;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\Part\Factory\MimePartFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\PartBuilderFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\UUEncodedPartFactory;
use ZBateson\MailMimeParser\Message\Part\MimePart;
use ZBateson\MailMimeParser\Message\Part\ParentHeaderPart;
use ZBateson\MailMimeParser\Message\Part\ParentPart;
use ZBateson\MailMimeParser\Message\PartFilter;

/**
 * 
 *
 * @author Zaahid Bateson
 */
class MessageHelper
{
    /**
     * @var MimePartFactory to create parts for attachments/content
     */
    protected $mimePartFactory;

    /**
     * @var UUEncodedPartFactory to create parts for attachments
     */
    protected $uuEncodedPartFactory;

    /**
     * @var PartBuilderFactory to create parts for attachments/content
     */
    protected $partBuilderFactory;

    /**
     * @param MimePartFactory $mimePartFactory
     * @param UUEncodedPartFactory $uuEncodedPartFactory
     * @param PartBuilderFactory $partBuilderFactory
     */
    public function __construct(
        MimePartFactory $mimePartFactory,
        UUEncodedPartFactory $uuEncodedPartFactory,
        PartBuilderFactory $partBuilderFactory
    ) {
        $this->mimePartFactory = $mimePartFactory;
        $this->uuEncodedPartFactory = $uuEncodedPartFactory;
        $this->partBuilderFactory = $partBuilderFactory;
    }

    /**
     * Copies the passed $header from $from, to $to or sets the header to
     * $default if it doesn't exist in $from.
     *
     * @param ParentHeaderPart $from
     * @param ParentHeaderPart $to
     * @param string $header
     * @param string $default
     */
    private function copyHeader(ParentHeaderPart $from, ParentHeaderPart $to, $header, $default = null)
    {
        $fromHeader = $from->getHeader($header);
        $set = ($fromHeader !== null) ? $fromHeader->getRawValue() : $default;
        if ($set !== null) {
            $to->setRawHeader($header, $set);
        }
    }

    /**
     * Copies Content-Type, Content-Disposition and Content-Transfer-Encoding
     * headers from the $from header into the $to header. If the Content-Type
     * header isn't defined in $from, defaults to text/plain with utf-8 and
     * quoted-printable.
     *
     * @param ParentHeaderPart $from
     * @param ParentHeaderPart $to
     */
    private function copyTypeHeadersAndContent(ParentHeaderPart $from, ParentHeaderPart $to, $move = false)
    {
        $this->copyHeader($from, $to, 'Content-Type', 'text/plain; charset=utf-8');
        if ($from->getHeader('Content-Type') === null) {
            $this->copyHeader($from, $to, 'Content-Transfer-Encoding', 'quoted-printable');
        } else {
            $this->copyHeader($from, $to, 'Content-Transfer-Encoding');
        }
        $this->copyHeader($from, $to, 'Content-Disposition');
        if ($from->hasContent()) {
            $to->attachContentStream($from->getContentStream(), MailMimeParser::DEFAULT_CHARSET);
        }
        if ($move) {
            $from->removeHeader('Content-Type');
            $from->removeHeader('Content-Transfer-Encoding');
            $from->removeHeader('Content-Disposition');
            $from->detachContentStream();
        }
    }

    /**
     * Creates and returns a unique boundary.
     *
     * @param string $mimeType first 3 characters of a multipart type are used,
     *      e.g. REL for relative or ALT for alternative
     * @return string
     */
    private function getUniqueBoundary($mimeType)
    {
        $type = ltrim(strtoupper(preg_replace('/^(multipart\/(.{3}).*|.*)$/i', '$2-', $mimeType)), '-');
        return uniqid('----=MMP-' . $type . '.', true);
    }

    /**
     * Creates a unique mime boundary and assigns it to the passed part's
     * Content-Type header with the passed mime type.
     *
     * @param MimePart $part
     * @param string $mimeType
     */
    private function setMimeHeaderBoundaryOnPart(MimePart $part, $mimeType)
    {
        $part->setRawHeader(
            'Content-Type',
            "$mimeType;\r\n\tboundary=\""
                . $this->getUniqueBoundary($mimeType) . '"'
        );
    }
    
    /**
     * Creates a new content part from the passed part, allowing the part to be
     * used for something else (e.g. changing a non-mime message to a multipart
     * mime message).
     *
     * @param MimePart $part
     * @return MimePart the newly-created MimePart
    */
    private function createNewContentPartFrom(MimePart $part)
    {
        $mime = $this->partBuilderFactory->newPartBuilder($this->mimePartFactory)->createMessagePart();
        $this->copyTypeHeadersAndContent($part, $mime, true);
        return $mime;
    }

    /**
     * Creates a new part out of the current contentPart and sets the message's
     * type to be multipart/mixed.
     */
    private function setMessageAsMixed(Message $message)
    {
        if ($message->hasContent()) {
            $part = $this->createNewContentPartFrom($message);
            $message->addChild($part, 0);
        }
        $this->setMimeHeaderBoundaryOnPart($message, 'multipart/mixed');
        $atts = $message->getAllAttachmentParts();
        if (!empty($atts)) {
            foreach ($atts as $att) {
                $att->markAsChanged();
            }
        }
    }

    /**
     * Sets this message to be a multipart/alternative message, making space for
     * a second content part.
     *
     * Creates a content part and assigns the content stream from the message to
     * that newly created part.
     */
    private function setMessageAsAlternative(Message $message)
    {
        if ($message->hasContent()) {
            $part = $this->createNewContentPartFrom($message);
            $message->addChild($part, 0);
        }
        $this->setMimeHeaderBoundaryOnPart($message, 'multipart/alternative');
    }

    /**
     * Returns the direct child of $alternativePart containing a part of
     * $mimeType.
     *
     * Used for alternative mime types that have a multipart/mixed or
     * multipart/related child containing a content part of $mimeType, where
     * the whole mixed/related part should be removed.
     *
     * @param string $mimeType the content-type to find below $alternativePart
     * @param MimePart $alternativePart The multipart/alternative part to look
     *        under
     * @return boolean|MimePart false if a part is not found
     */
    private function getContentPartContainerFromAlternative($mimeType, MimePart $alternativePart)
    {
        $part = $alternativePart->getPart(0, PartFilter::fromInlineContentType($mimeType));
        $contPart = null;
        do {
            if ($part === null) {
                return false;
            }
            $contPart = $part;
            $part = $part->getParent();
        } while ($part !== $alternativePart);
        return $contPart;
    }

    /**
     * Removes all parts of $mimeType from $alternativePart.
     *
     * If $alternativePart contains a multipart/mixed or multipart/relative part
     * with other parts of different content-types, the multipart part is
     * removed, and parts of different content-types can optionally be moved to
     * the main message part.
     *
     * @param Message $message
     * @param string $mimeType
     * @param MimePart $alternativePart
     * @param bool $keepOtherContent
     * @return bool
     */
    private function removeAllContentPartsFromAlternative(Message $message, $mimeType, MimePart $alternativePart, $keepOtherContent)
    {
        $rmPart = $this->getContentPartContainerFromAlternative($mimeType, $alternativePart);
        if ($rmPart === false) {
            return false;
        }
        if ($keepOtherContent) {
            $this->moveAllPartsAsAttachmentsExcept($message, $rmPart, $mimeType);
            $alternativePart = $message->getPart(0, PartFilter::fromInlineContentType('multipart/alternative'));
        } else {
            $rmPart->removeAllParts();
        }
        $message->removePart($rmPart);
        if ($alternativePart !== null) {
            if ($alternativePart->getChildCount() === 1) {
                $this->replacePart($message, $alternativePart, $alternativePart->getChild(0));
            } elseif ($alternativePart->getChildCount() === 0) {
                $message->removePart($alternativePart);
            }
        }
        while ($message->getChildCount() === 1) {
            $this->replacePart($message, $message, $message->getChild(0));
        }
        return true;
    }

    /**
     * Creates a new mime part as a multipart/alternative and assigns the passed
     * $contentPart as a part below it before returning it.
     *
     * @param Message $message
     * @param MimePart $contentPart
     * @return MimePart the alternative part
     */
    private function createAlternativeContentPart(Message $message, MimePart $contentPart)
    {
        $altPart = $this->partBuilderFactory->newPartBuilder($this->mimePartFactory)->createMessagePart();
        $this->setMimeHeaderBoundaryOnPart($altPart, 'multipart/alternative');
        $message->removePart($contentPart);
        $message->addChild($altPart, 0);
        $altPart->addChild($contentPart, 0);
        return $altPart;
    }

    /**
     * Copies type headers (Content-Type, Content-Disposition,
     * Content-Transfer-Encoding) from the $from MimePart to $to.  Attaches the
     * content resource handle of $from to $to, and loops over child parts,
     * removing them from $from and adding them to $to.
     *
     * @param MimePart $from
     * @param MimePart $to
     */
    private function movePartContentAndChildren(MimePart $from, MimePart $to)
    {
        $this->copyTypeHeadersAndContent($from, $to, true);
        foreach ($from->getChildParts() as $child) {
            $from->removePart($child);
            $to->addChild($child);
        }
    }

    /**
     * Moves all parts under $from into this message except those with a
     * content-type equal to $exceptMimeType.  If the message is not a
     * multipart/mixed message, it is set to multipart/mixed first.
     *
     * @param Message $message
     * @param MimePart $from
     * @param string $exceptMimeType
     */
    private function moveAllPartsAsAttachmentsExcept(Message $message, MimePart $from, $exceptMimeType)
    {
        $parts = $from->getAllParts(new PartFilter([
            'multipart' => PartFilter::FILTER_EXCLUDE,
            'headers' => [
                PartFilter::FILTER_EXCLUDE => [
                    'Content-Type' => $exceptMimeType
                ]
            ]
        ]));
        if (strcasecmp($message->getContentType(), 'multipart/mixed') !== 0) {
            $this->setMessageAsMixed($message);
        }
        foreach ($parts as $part) {
            $from->removePart($part);
            $message->addChild($part);
        }
    }

    /**
     * Replaces the $part MimePart with $replacement.
     *
     * Essentially removes $part from its parent, and adds $replacement in its
     * same position.  If $part is this Message, its type headers are moved from
     * this message to $replacement, the content resource is moved, and children
     * are assigned to $replacement.
     *
     * @param MimePart $part
     * @param MimePart $replacement
     */
    private function replacePart(Message $message, MimePart $part, MimePart $replacement)
    {
        $message->removePart($replacement);
        if ($part === $message) {
            $this->movePartContentAndChildren($replacement, $part);
            return;
        }
        $parent = $part->getParent();
        $position = $parent->removePart($part);
        $parent->addChild($replacement, $position);
    }

    /**
     * Enforces the message to be a mime message for a non-mime (e.g. uuencoded
     * or unspecified) message.  If the message has uuencoded attachments, sets
     * up the message as a multipart/mixed message and creates a content part.
     */
    public function enforceMime(Message $message)
    {
        if (!$message->isMime()) {
            if ($message->getAttachmentCount()) {
                $this->setMessageAsMixed($message);
            } else {
                $message->setRawHeader('Content-Type', "text/plain;\r\n\tcharset=\"iso-8859-1\"");
            }
            $message->setRawHeader('Mime-Version', '1.0');
        }
    }

    /**
     * Creates a multipart/related part out of 'inline' children of $parent and
     * returns it.
     *
     * @param MimePart $parent
     * @return MimePart
     */
    private function createMultipartRelatedPartForInlineChildrenOf(MimePart $parent)
    {
        $builder = $this->partBuilderFactory->newPartBuilder($this->mimePartFactory);
        $relatedPart = $builder->createMessagePart();
        $this->setMimeHeaderBoundaryOnPart($relatedPart, 'multipart/related');
        foreach ($parent->getChildParts(PartFilter::fromDisposition('inline', PartFilter::FILTER_EXCLUDE)) as $part) {
            $this->removePart($part);
            $relatedPart->addChild($part);
        }
        $parent->addChild($relatedPart, 0);
        return $relatedPart;
    }

    /**
     * Finds an alternative inline part in the message and returns it if one
     * exists.
     *
     * If the passed $mimeType is text/plain, searches for a text/html part.
     * Otherwise searches for a text/plain part to return.
     *
     * @param string $mimeType
     * @return MimeType or null if not found
     */
    private function findOtherContentPartFor(Message $message, $mimeType)
    {
        $altPart = $message->getPart(
            0,
            PartFilter::fromInlineContentType(($mimeType === 'text/plain') ? 'text/html' : 'text/plain')
        );
        if ($altPart !== null && $altPart->getParent() !== null && $altPart->getParent()->isMultiPart()) {
            $altPartParent = $altPart->getParent();
            if ($altPartParent->getPartCount(PartFilter::fromDisposition('inline', PartFilter::FILTER_EXCLUDE)) !== 1) {
                $altPart = $this->createMultipartRelatedPartForInlineChildrenOf($altPartParent);
            }
        }
        return $altPart;
    }

    /**
     * Creates a new content part for the passed mimeType and charset, making
     * space by creating a multipart/alternative if needed
     *
     * @param Message $message
     * @param string $mimeType
     * @param string $charset
     * @return MimePart
     */
    private function createContentPartForMimeType(Message $message, $mimeType, $charset)
    {
        $builder = $this->partBuilderFactory->newPartBuilder($this->mimePartFactory);
        $builder->addHeader('Content-Type', "$mimeType;\r\n\tcharset=\"$charset\"");
        $builder->addHeader('Content-Transfer-Encoding', 'quoted-printable');
        $this->enforceMime($message);
        $mimePart = $builder->createMessagePart();

        $altPart = $this->findOtherContentPartFor($message, $mimeType);

        if ($altPart === $message) {
            $this->setMessageAsAlternative($message);
            $message->addChild($mimePart);
        } elseif ($altPart !== null) {
            $mimeAltPart = $this->createAlternativeContentPart($message, $altPart);
            $mimeAltPart->addChild($mimePart, 1);
        } else {
            $message->addChild($mimePart, 0);
        }

        return $mimePart;
    }

    /**
     * Creates and returns a MimePart for use with a new attachment part being
     * created.
     *
     * @return \ZBateson\MailMimeParser\Message\Part\MessagePart
     */
    public function createPartForAttachment(Message $message, $mimeType, $filename, $disposition)
    {
        $safe = iconv('UTF-8', 'US-ASCII//translit//ignore', $filename);
        if ($message->isMime()) {
            $builder = $this->partBuilderFactory->newPartBuilder($this->mimePartFactory);
            $builder->addHeader('Content-Transfer-Encoding', 'base64');
            if (strcasecmp($message->getContentType(), 'multipart/mixed') !== 0) {
                $this->setMessageAsMixed($message);
            }
            $builder->addHeader('Content-Type', "$mimeType;\r\n\tname=\"$safe\"");
            $builder->addHeader('Content-Disposition', "$disposition;\r\n\tfilename=\"$safe\"");
        } else {
            $builder = $this->partBuilderFactory->newPartBuilder(
                $this->uuEncodedPartFactory
            );
            $builder->setProperty('filename', $safe);
        }
        return $builder->createMessagePart();
    }

    /**
     * Removes the content part of the message with the passed mime type.  If
     * there is a remaining content part and it is an alternative part of the
     * main message, the content part is moved to the message part.
     *
     * If the content part is part of an alternative part beneath the message,
     * the alternative part is replaced by the remaining content part,
     * optionally keeping other parts if $keepOtherContent is set to true.
     *
     * @param Message $message
     * @param string $mimeType
     * @param bool $keepOtherContent
     * @return boolean true on success
     */
    public function removeAllContentPartsByMimeType(Message $message, $mimeType, $keepOtherContent = false)
    {
        $alt = $message->getPart(0, PartFilter::fromInlineContentType('multipart/alternative'));
        if ($alt !== null) {
            return $this->removeAllContentPartsFromAlternative($message, $mimeType, $alt, $keepOtherContent);
        }
        $message->removeAllParts(PartFilter::fromInlineContentType($mimeType));
        return true;
    }

    /**
     * Removes the 'inline' part with the passed contentType, at the given index
     * defaulting to the first
     *
     * @param Message $message
     * @param string $mimeType
     * @param int $index
     * @return boolean true on success
     */
    public function removePartByMimeType(Message $message, $mimeType, $index = 0)
    {
        $parts = $message->getAllParts(PartFilter::fromInlineContentType($mimeType));
        $alt = $message->getPart(0, PartFilter::fromInlineContentType('multipart/alternative'));
        if ($parts === null || !isset($parts[$index])) {
            return false;
        } elseif (count($parts) === 1) {
            return $this->removeAllContentPartsByMimeType($message, $mimeType, true);
        }
        $part = $parts[$index];
        $message->removePart($part);
        if ($alt !== null && $alt->getChildCount() === 1) {
            $this->replacePart($message, $alt, $alt->getChild(0));
        }
        return true;
    }

    /**
     * Either creates a mime part or sets the existing mime part with the passed
     * mimeType to $strongOrHandle.
     *
     * @param Message $message
     * @param string $mimeType
     * @param string|resource $stringOrHandle
     * @param string $charset
     */
    public function setContentPartForMimeType(Message $message, $mimeType, $stringOrHandle, $charset)
    {
        $part = ($mimeType === 'text/html') ? $message->getHtmlPart() : $message->getTextPart();
        if ($part === null) {
            $part = $this->createContentPartForMimeType($message, $mimeType, $charset);
        } else {
            $contentType = $part->getHeaderValue('Content-Type', 'text/plain');
            $part->setRawHeader('Content-Type', "$contentType;\r\n\tcharset=\"$charset\"");
        }
        $part->setContent($stringOrHandle);
    }

    /**
     * This function makes space by moving the main message part down one level.
     *
     * The content-type, content-disposition and content-transfer-encoding
     * headers are copied from this message to the newly created part, the
     * resource handle is moved and detached, any attachments and content parts
     * with parents set to this message get their parents set to the newly
     * created part.
     */
    public function setMessageAsMultipartSigned(Message $message, $micalg, $protocol)
    {
        $this->enforceMime($message);
        $messagePart = $this->partBuilderFactory->newPartBuilder($this->mimePartFactory)->createMessagePart();
        $this->movePartContentAndChildren($message, $messagePart);
        $message->addChild($messagePart);

        $boundary = $this->getUniqueBoundary('multipart/signed');
        $message->setRawHeader(
            'Content-Type',
            "multipart/signed;\r\n\tboundary=\"$boundary\";\r\n\tmicalg=\"$micalg\"; protocol=\"$protocol\""
        );
    }

    /**
     * Sets the signature of the message to $body, creating a signature part if
     * one doesn't exist.
     *
     * @param string $body
     */
    public function setSignature(Message $message, $body)
    {
        $signedPart = $message->getSignaturePart();
        if ($signedPart === null) {
            $signedPart = $this->partBuilderFactory->newPartBuilder($this->mimePartFactory)->createMessagePart();
            $message->addChild($signedPart);
        }
        $signedPart->setRawHeader(
            'Content-Type',
            $message->getHeaderParameter('Content-Type', 'protocol')
        );
        $signedPart->setContent($body);
    }

    /**
     * Loops over parts of this message and sets the content-transfer-encoding
     * header to quoted-printable for text/* mime parts, and to base64
     * otherwise for parts that are '8bit' encoded.
     *
     * Used for multipart/signed messages which doesn't support 8bit transfer
     * encodings.
     */
    public function overwrite8bitContentEncoding(Message $message)
    {
        $parts = $message->getAllParts(new PartFilter([
            'headers' => [ PartFilter::FILTER_INCLUDE => [
                'Content-Transfer-Encoding' => '8bit'
            ] ]
        ]));
        foreach ($parts as $part) {
            $contentType = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
            if ($contentType === 'text/plain' || $contentType === 'text/html') {
                $part->setRawHeader('Content-Transfer-Encoding', 'quoted-printable');
            } else {
                $part->setRawHeader('Content-Transfer-Encoding', 'base64');
            }
        }
    }

    /**
     * Ensures a non-text part comes first in a signed multipart/alternative
     * message as some clients seem to prefer the first content part if the
     * client doesn't understand multipart/signed.
     */
    public function ensureHtmlPartFirstForSignedMessage(Message $message)
    {
        $alt = $message->getPartByMimeType('multipart/alternative');
        if ($alt !== null && $alt instanceof ParentPart) {
            $cont = $this->getContentPartContainerFromAlternative('text/html', $alt);
            $children = $alt->getChildParts();
            $pos = array_search($cont, $children, true);
            if ($pos !== false && $pos !== 0) {
                $alt->removePart($children[0]);
                $alt->addChild($children[0]);
            }
        }
    }
}
