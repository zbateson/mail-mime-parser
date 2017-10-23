<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\ParameterHeader;
use ZBateson\MailMimeParser\Message\Writer\MimePartWriter;

/**
 * Represents a single part of a multi-part mime message.
 *
 * A MimePart object may have any number of child parts, or may be a child
 * itself with its own parent or parents.
 *
 * The content of the part can be read from its PartStream resource handle,
 * accessible via MimePart::getContentResourceHanlde.
 *
 * @author Zaahid Bateson
 */
class WritableMessage
{
    /**
     * @var \ZBateson\MailMimeParser\Message\Part\MimePartFactory a MimePartFactory to create
     *      parts for attachments/content
     */
    protected $mimePartFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\Writer\MessageWriter the part
     *      writer for this Message.  The same object is assigned to $partWriter
     *      but as an AbstractWriter -- not really needed in PHP but helps with
     *      auto-complete and code analyzers.
     */
    protected $messageWriter = null;

    /**
     * Creates a unique mime boundary and assigns it to the passed part's
     * Content-Type header with the passed mime type.
     * 
     * @param \ZBateson\MailMimeParser\Message\Part\MimePart $part
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
     * Sets this message to be a multipart/alternative message, making space for
     * a second content part.
     * 
     * Creates a content part and assigns the content stream from the message to
     * that newly created part.
     */
    private function setMessageAsAlternative()
    {
        $contentPart = $this->mimePartFactory->newMimePart();
        $contentPart->attachContentResourceHandle($this->handle);
        $this->detachContentResourceHandle();
        $contentType = 'text/plain; charset="us-ascii"';
        $contentHeader = $this->getHeader('Content-Type');
        if ($contentHeader !== null) {
            $contentType = $contentHeader->getRawValue();
        }
        $contentPart->setRawHeader('Content-Type', $contentType);
        $this->setMimeHeaderBoundaryOnPart($this, 'multipart/alternative');
        $this->addPart($contentPart, 0);
    }

    /**
     * Moves all parts under $from into this message except those with a
     * content-type equal to $exceptMimeType.  If the message is not a
     * multipart/mixed message, it is set to multipart/mixed first.
     * 
     * @param MimePart $from
     * @param string $exceptMimeType
     */
    private function moveAllPartsAsAttachmentsExcept(MimePart $from, $exceptMimeType)
    {
        $parts = $from->getAllParts(new PartFilter([
            'multipart' => PartFilter::FILTER_EXCLUDE,
            'headers' => [
                PartFilter::FILTER_EXCLUDE => [
                    'Content-Type' => $exceptMimeType
                ]
            ]
        ]));
        if ($this->getHeaderValue('Content-Type') !== 'multipart/mixed') {
            $this->setMessageAsMixed();
        }
        foreach ($parts as $part) {
            $from->removePart($part);
            $this->addPart($part);
        }
    }

    /**
     * Returns an open resource handle for the passed string or resource handle.
     * 
     * For a string, creates a php://temp stream and returns it.
     * 
     * @param resource|string $stringOrHandle
     * @return resource
     */
    private function getHandleForStringOrHandle($stringOrHandle)
    {
        $tempHandle = fopen('php://temp', 'r+');
        if (is_string($stringOrHandle)) {
            fwrite($tempHandle, $stringOrHandle);
        } else {
            stream_copy_to_stream($stringOrHandle, $tempHandle);
        }
        rewind($tempHandle);
        return $tempHandle;
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
        return uniqid('----=MMP-' . $type . $this->objectId . '.', true);
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
     * @param string $mimeType
     * @param MimePart $alternativePart
     * @param bool $keepOtherContent
     * @return bool
     */
    private function removeAllContentPartsFromAlternative($mimeType, $alternativePart, $keepOtherContent)
    {
        $rmPart = $this->getContentPartContainerFromAlternative($mimeType, $alternativePart);
        if ($rmPart === false) {
            return false;
        }
        if ($keepOtherContent) {
            $this->moveAllPartsAsAttachmentsExcept($rmPart, $mimeType);
            $alternativePart = $this->getPart(0, PartFilter::fromInlineContentType('multipart/alternative'));
        } else {
            $rmPart->removeAllParts();
        }
        $this->removePart($rmPart);
        if ($alternativePart !== null) {
            if ($alternativePart->getChildCount() === 1) {
                $this->replacePart($alternativePart, $alternativePart->getChild(0));
            } elseif ($alternativePart->getChildCount() === 0) {
                $this->removePart($alternativePart);
            }
        }
        while ($this->getChildCount() === 1) {
            $this->replacePart($this, $this->getChild(0));
        }
        return true;
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
     * @param string $mimeType
     * @param bool $keepOtherContent
     * @return boolean true on success
     */
    protected function removeAllContentPartsByMimeType($mimeType, $keepOtherContent = false)
    {
        $alt = $this->getPart(0, PartFilter::fromInlineContentType('multipart/alternative'));
        if ($alt !== null) {
            return $this->removeAllContentPartsFromAlternative($mimeType, $alt, $keepOtherContent);
        }
        $this->removeAllParts(PartFilter::fromInlineContentType($mimeType));
        return true;
    }
    
    /**
     * Removes the 'inline' part with the passed contentType, at the given index
     * defaulting to the first 
     * 
     * @param string $contentType
     * @param int $index
     * @return boolean true on success
     */
    protected function removePartByMimeType($mimeType, $index = 0)
    {
        $parts = $this->getAllParts(PartFilter::fromInlineContentType($mimeType));
        $alt = $this->getPart(0, PartFilter::fromInlineContentType('multipart/alternative'));
        if ($parts === null || !isset($parts[$index])) {
            return false;
        } elseif (count($parts) === 1) {
            return $this->removeAllContentPartsByMimeType($mimeType, true);
        }
        $part = $parts[$index];
        $this->removePart($part);
        if ($alt !== null && $alt->getChildCount() === 1) {
            $this->replacePart($alt, $alt->getChild(0));
        }
        return true;
    }
    
    /**
     * Creates a new mime part as a multipart/alternative and assigns the passed
     * $contentPart as a part below it before returning it.
     * 
     * @param MimePart $contentPart
     * @return MimePart the alternative part
     */
    private function createAlternativeContentPart(MimePart $contentPart)
    {
        $altPart = $this->mimePartFactory->newMimePart();
        $this->setMimeHeaderBoundaryOnPart($altPart, 'multipart/alternative');
        $this->removePart($contentPart);
        $this->addPart($altPart, 0);
        $altPart->addPart($contentPart, 0);
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
    private function movePartContentAndChildrenToPart(MimePart $from, MimePart $to)
    {
        $this->copyTypeHeadersFromPartToPart($from, $to);
        $to->attachContentResourceHandle($from->getContentResourceHandle());
        $from->detachContentResourceHandle();
        foreach ($from->getChildParts() as $child) {
            $from->removePart($child);
            $to->addPart($child);
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
    private function replacePart(MimePart $part, MimePart $replacement)
    {
        $this->removePart($replacement);
        if ($part === $this) {
            $this->movePartContentAndChildrenToPart($replacement, $part);
            return;
        }
        $parent = $part->getParent();
        $position = $parent->removePart($part);
        $parent->addPart($replacement, $position);
    }

    /**
     * Copies Content-Type, Content-Disposition and Content-Transfer-Encoding
     * headers from the $from header into the $to header. If the Content-Type
     * header isn't defined in $from, defaults to text/plain and
     * quoted-printable.
     * 
     * @param \ZBateson\MailMimeParser\Message\Part\MimePart $from
     * @param \ZBateson\MailMimeParser\Message\Part\MimePart $to
     */
    private function copyTypeHeadersFromPartToPart(MimePart $from, MimePart $to)
    {
        $typeHeader = $from->getHeader('Content-Type');
        if ($typeHeader !== null) {
            $to->setRawHeader('Content-Type', $typeHeader->getRawValue());
            $encodingHeader = $from->getHeader('Content-Transfer-Encoding');
            if ($encodingHeader !== null) {
                $to->setRawHeader('Content-Transfer-Encoding', $encodingHeader->getRawValue());
            }
            $dispositionHeader = $from->getHeader('Content-Disposition');
            if ($dispositionHeader !== null) {
                $to->setRawHeader('Content-Disposition', $dispositionHeader->getRawValue());
            }
        } else {
            $to->setRawHeader('Content-Type', 'text/plain;charset=us-ascii');
            $to->setRawHeader('Content-Transfer-Encoding', 'quoted-printable');
        }
    }
    
    /**
     * Creates a new content part from the passed part, allowing the part to be
     * used for something else (e.g. changing a non-mime message to a multipart
     * mime message).
     * 
     * @param MimePart $part
     * @return MimePart the newly-created MimePart   
    */
    private function createNewContentPartFromPart(MimePart $part)
    {
        $contPart = $this->mimePartFactory->newMimePart();
        $this->copyTypeHeadersFromPartToPart($part, $contPart);
        $contPart->attachContentResourceHandle($part->handle);
        $part->detachContentResourceHandle();
        return $contPart;
    }
    
    /**
     * Creates a new part out of the current contentPart and sets the message's
     * type to be multipart/mixed.
     */
    private function setMessageAsMixed()
    {
        if ($this->handle !== null) {
            $part = $this->createNewContentPartFromPart($this);
            $this->addPart($part, 0);
        }
        $this->setMimeHeaderBoundaryOnPart($this, 'multipart/mixed');
        $this->removeHeader('Content-Transfer-Encoding');
        $this->removeHeader('Content-Disposition');
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
    private function makeSpaceForMultipartSignedMessage()
    {
        $this->enforceMime();
        $messagePart = $this->mimePartFactory->newMimePart();

        $this->copyTypeHeadersFromPartToPart($this, $messagePart);
        $messagePart->attachContentResourceHandle($this->handle);
        $this->detachContentResourceHandle();
        
        foreach ($this->getChildParts() as $part) {
            $this->removePart($part);
            $messagePart->addPart($part);
        }
        $this->addPart($messagePart, 0);
    }
    
    /**
     * Creates and returns a new MimePart for the signature part of a
     * multipart/signed message
     * 
     * @param string $body
     */
    public function createSignaturePart($body)
    {
        $signedPart = $this->getSignaturePart();
        if ($signedPart === null) {
            $signedPart = $this->mimePartFactory->newMimePart();
            $this->addPart($signedPart);
        }
        $signedPart->setRawHeader(
            'Content-Type',
            $this->getHeaderParameter('Content-Type', 'protocol')
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
    private function overwrite8bitContentEncoding()
    {
        $parts = $this->getAllParts(new PartFilter([
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
    private function ensureHtmlPartFirstForSignedMessage()
    {
        $alt = $this->getPartByMimeType('multipart/alternative');
        if ($alt !== null) {
            $cont = $this->getContentPartContainerFromAlternative('text/html', $alt);
            $pos = array_search($cont, $alt->parts, true);
            if ($pos !== false && $pos !== 0) {
                $tmp = $alt->parts[0];
                $alt->parts[0] = $alt->parts[$pos];
                $alt->parts[$pos] = $tmp;
            }
        }
    }
    
    /**
     * Turns the message into a multipart/signed message, moving the actual
     * message into a child part, sets the content-type of the main message to
     * multipart/signed and adds a signature part as well.
     * 
     * @param string $micalg The Message Integrity Check algorithm being used
     * @param string $protocol The mime-type of the signature body
     */
    public function setAsMultipartSigned($micalg, $protocol)
    {
        $contentType = $this->getHeaderValue('Content-Type', 'text/plain');
        if (strcasecmp($contentType, 'multipart/signed') !== 0) {
            $this->makeSpaceForMultipartSignedMessage();
            $boundary = $this->getUniqueBoundary('multipart/signed');
            $this->setRawHeader(
                'Content-Type',
                "multipart/signed;\r\n\tboundary=\"$boundary\";\r\n\tmicalg=\"$micalg\"; protocol=\"$protocol\""
            );
            $this->removeHeader('Content-Disposition');
            $this->removeHeader('Content-Transfer-Encoding');
        }
        $this->overwrite8bitContentEncoding();
        $this->ensureHtmlPartFirstForSignedMessage();
        $this->createSignaturePart('Not set');
    }
    
    /**
     * Returns the signed part or null if not set.
     * 
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function getSignaturePart()
    {
        return $this->getChild(0, new PartFilter([ 'signedpart' => PartFilter::FILTER_INCLUDE ]));
    }
    
    /**
     * Enforces the message to be a mime message for a non-mime (e.g. uuencoded
     * or unspecified) message.  If the message has uuencoded attachments, sets
     * up the message as a multipart/mixed message and creates a content part.
     */
    private function enforceMime()
    {
        if (!$this->isMime()) {
            if ($this->getAttachmentCount()) {
                $this->setMessageAsMixed();
            } else {
                $this->setRawHeader('Content-Type', "text/plain;\r\n\tcharset=\"us-ascii\"");
            }
            $this->setRawHeader('Mime-Version', '1.0');
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
        $relatedPart = $this->mimePartFactory->newMimePart();
        $this->setMimeHeaderBoundaryOnPart($relatedPart, 'multipart/related');
        foreach ($parent->getChildParts(PartFilter::fromDisposition('inline', PartFilter::FILTER_EXCLUDE)) as $part) {
            $this->removePart($part);
            $relatedPart->addPart($part);
        }
        $parent->addPart($relatedPart, 0);
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
    private function findOtherContentPartFor($mimeType)
    {
        $altPart = $this->getPart(
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
     * @param string $mimeType
     * @param string $charset
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    private function createContentPartForMimeType($mimeType, $charset)
    {
        $mimePart = $this->mimePartFactory->newMimePart();
        $mimePart->setRawHeader('Content-Type', "$mimeType;\r\n\tcharset=\"$charset\"");
        $mimePart->setRawHeader('Content-Transfer-Encoding', 'quoted-printable');
        $this->enforceMime();
        
        $altPart = $this->findOtherContentPartFor($mimeType);
        
        if ($altPart === $this) {
            $this->setMessageAsAlternative();
            $this->addPart($mimePart);
        } elseif ($altPart !== null) {
            $mimeAltPart = $this->createAlternativeContentPart($altPart);
            $mimeAltPart->addPart($mimePart, 1);
        } else {
            $this->addPart($mimePart, 0);
        }
        
        return $mimePart;
    }
    
    /**
     * Either creates a mime part or sets the existing mime part with the passed
     * mimeType to $strongOrHandle.
     * 
     * @param string $mimeType
     * @param string|resource $stringOrHandle
     * @param string $charset
     */
    protected function setContentPartForMimeType($mimeType, $stringOrHandle, $charset)
    {
        $part = ($mimeType === 'text/html') ? $this->getHtmlPart() : $this->getTextPart();
        $handle = $this->getHandleForStringOrHandle($stringOrHandle);
        if ($part === null) {
            $part = $this->createContentPartForMimeType($mimeType, $charset);
        } else {
            $contentType = $part->getHeaderValue('Content-Type', 'text/plain');
            $part->setRawHeader('Content-Type', "$contentType;\r\n\tcharset=\"$charset\"");
        }
        $part->attachContentResourceHandle($handle);
    }
    
    /**
     * Sets the text/plain part of the message to the passed $stringOrHandle,
     * either creating a new part if one doesn't exist for text/plain, or
     * assigning the value of $stringOrHandle to an existing text/plain part.
     * 
     * The optional $charset parameter is the charset for saving to.
     * $stringOrHandle is expected to be in UTF-8 regardless of the target
     * charset.
     * 
     * @param string|resource $stringOrHandle
     * @param string $charset
     */
    public function setTextPart($stringOrHandle, $charset = 'UTF-8')
    {
        $this->setContentPartForMimeType('text/plain', $stringOrHandle, $charset);
    }
    
    /**
     * Sets the text/html part of the message to the passed $stringOrHandle,
     * either creating a new part if one doesn't exist for text/html, or
     * assigning the value of $stringOrHandle to an existing text/html part.
     * 
     * The optional $charset parameter is the charset for saving to.
     * $stringOrHandle is expected to be in UTF-8 regardless of the target
     * charset.
     * 
     * @param string|resource $stringOrHandle
     * @param string $charset
     */
    public function setHtmlPart($stringOrHandle, $charset = 'UTF-8')
    {
        $this->setContentPartForMimeType('text/html', $stringOrHandle, $charset);
    }
    
    /**
     * Removes the text/plain part of the message at the passed index if one
     * exists.  Returns true on success.
     * 
     * @return bool true on success
     */
    public function removeTextPart($index = 0)
    {
        return $this->removePartByMimeType('text/plain', $index);
    }

    /**
     * Removes all text/plain inline parts in this message, optionally keeping
     * other inline parts as attachments on the main message (defaults to
     * keeping them).
     * 
     * @param bool $keepOtherPartsAsAttachments
     * @return bool true on success
     */
    public function removeAllTextParts($keepOtherPartsAsAttachments = true)
    {
        return $this->removeAllContentPartsByMimeType('text/plain', $keepOtherPartsAsAttachments);
    }
    
    /**
     * Removes the html part of the message if one exists.  Returns true on
     * success.
     * 
     * @return bool true on success
     */
    public function removeHtmlPart($index = 0)
    {
        return $this->removePartByMimeType('text/html', $index);
    }
    
    /**
     * Removes all text/html inline parts in this message, optionally keeping
     * other inline parts as attachments on the main message (defaults to
     * keeping them).
     * 
     * @param bool $keepOtherPartsAsAttachments
     * @return bool true on success
     */
    public function removeAllHtmlParts($keepOtherPartsAsAttachments = true)
    {
        return $this->removeAllContentPartsByMimeType('text/html', $keepOtherPartsAsAttachments);
    }
    
    /**
     * Removes the attachment with the given index
     * 
     * @param int $index
     */
    public function removeAttachmentPart($index)
    {
        $part = $this->getAttachmentPart($index);
        $this->removePart($part);
    }
    
    /**
     * Creates and returns a MimePart for use with a new attachment part being
     * created.
     * 
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    protected function createPartForAttachment()
    {
        if ($this->isMime()) {
            $part = $this->mimePartFactory->newMimePart();
            $part->setRawHeader('Content-Transfer-Encoding', 'base64');
            if ($this->getHeaderValue('Content-Type') !== 'multipart/mixed') {
                $this->setMessageAsMixed();
            }
            return $part;
        }
        return $this->mimePartFactory->newUUEncodedPart();
    }
    
    /**
     * Adds an attachment part for the passed raw data string or handle and
     * given parameters.
     * 
     * @param string|handle $stringOrHandle
     * @param strubg $mimeType
     * @param string $filename
     * @param string $disposition
     */
    public function addAttachmentPart($stringOrHandle, $mimeType, $filename = null, $disposition = 'attachment')
    {
        if ($filename === null) {
            $filename = 'file' . uniqid();
        }
        $filename = iconv('UTF-8', 'US-ASCII//translit//ignore', $filename);
        $part = $this->createPartForAttachment();
        $part->setRawHeader('Content-Type', "$mimeType;\r\n\tname=\"$filename\"");
        $part->setRawHeader('Content-Disposition', "$disposition;\r\n\tfilename=\"$filename\"");
        $part->attachContentResourceHandle($this->getHandleForStringOrHandle($stringOrHandle));
        $this->addPart($part);
    }
    
    /**
     * Adds an attachment part using the passed file.
     * 
     * Essentially creates a file stream and uses it.
     * 
     * @param string $file
     * @param string $mimeType
     * @param string $filename
     * @param string $disposition
     */
    public function addAttachmentPartFromFile($file, $mimeType, $filename = null, $disposition = 'attachment')
    {
        $handle = fopen($file, 'r');
        if ($filename === null) {
            $filename = basename($file);
        }
        $filename = iconv('UTF-8', 'US-ASCII//translit//ignore', $filename);
        $part = $this->createPartForAttachment();
        $part->setRawHeader('Content-Type', "$mimeType;\r\n\tname=\"$filename\"");
        $part->setRawHeader('Content-Disposition', "$disposition;\r\n\tfilename=\"$filename\"");
        $part->attachContentResourceHandle($handle);
        $this->addPart($part);
    }

    /**
     * Saves the message as a MIME message to the passed resource handle.
     * 
     * @param resource $handle
     */
    public function save($handle)
    {
        $this->messageWriter->writeMessageTo($this, $handle);
    }

    /**
     * Returns the content part of a signed message for a signature to be
     * calculated on the message.
     * 
     * @return string
     */
    public function getSignableBody()
    {
        return $this->messageWriter->getSignableBody($this);
    }
}
