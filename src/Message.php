<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Message\MimePartFactory;
use ZBateson\MailMimeParser\Message\Writer\MessageWriter;
use ZBateson\MailMimeParser\Message\PartFilter;

/**
 * A parsed mime message with optional mime parts depending on its type.
 * 
 * A mime message may have any number of mime parts, and each part may have any
 * number of sub-parts, etc...
 *
 * @author Zaahid Bateson
 */
class Message extends MimePart
{
    /**
     * @var string unique ID used to identify the object to
     *      $this->partStreamRegistry when registering the stream.  The ID is
     *      used for opening stream parts with the mmp-mime-message "protocol".
     * 
     * @see \ZBateson\MailMimeParser\SimpleDi::registerStreamExtensions
     * @see \ZBateson\MailMimeParser\Stream\PartStream::stream_open
     */
    protected $objectId;

    /**
     * @var \ZBateson\MailMimeParser\Message\MimePartFactory a MimePartFactory to create
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
     * Convenience method to parse a handle or string into a Message without
     * requiring including MailMimeParser, instantiating it, and calling parse.
     * 
     * @param resource|string $handleOrString the resource handle to the input
     *        stream of the mime message, or a string containing a mime message
     */
    public static function from($handleOrString)
    {
        $mmp = new MailMimeParser();
        return $mmp->parse($handleOrString);
    }
    
    /**
     * Constructs a Message.
     * 
     * @param HeaderFactory $headerFactory
     * @param MessageWriter $messageWriter
     * @param MimePartFactory $mimePartFactory
     */
    public function __construct(
        HeaderFactory $headerFactory,   
        MessageWriter $messageWriter,
        MimePartFactory $mimePartFactory
    ) {
        parent::__construct($headerFactory, $messageWriter);
        $this->messageWriter = $messageWriter;
        $this->mimePartFactory = $mimePartFactory;
        $this->objectId = uniqid();
    }
    
    /**
     * Returns the unique object ID registered with the PartStreamRegistry
     * service object.
     * 
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }
    
    private function replacePart(MimePart $part, MimePart $replacement)
    {
        if ($part === $this) {
            // remove part first -- needs content-type header to de-register
            $this->removePart($part);
            $this->copyTypeHeadersFromPartToPart($replacement, $part);
            $this->attachContentResourceHandle($replacement->getContentResourceHandle());
            $replacement->detachContentResourceHandle();
            foreach ($replacement->getChildParts() as $child) {
                $this->removePart($child);
                $child->setParent($part);
                $this->addPart($child);
            }
            $this->addPart($part, 0);
            $this->removePart($replacement);
        } else {
            $parent = $part->getParent();
            $index = array_search($part, $parent->parts, true);
            $this->removePart($part);
            $this->removePart($replacement);
            $replacement->setParent($parent);
            $this->addPart($replacement, $index);
        }
    }
    
    private function getContentPartContainerFromAlternative($contentType, $alternativePart)
    {
        $part = $alternativePart->getPart(0, new PartFilter([ 'headers' => 
            [ 
                PartFilter::FILTER_INCLUDE => [
                    'Content-Type' => $contentType
                ],
                PartFilter::FILTER_EXCLUDE => [
                    'Content-Disposition' => 'attachment'
                ]
            ]
        ]));
        $contPart = null;
        if ($part === null) {
            return false;
        }
        do {
            $contPart = $part;
            $part = $part->getParent();
        } while ($part !== $alternativePart);
        return $contPart;
    }

    /**
     * 
     * @param type $contentType
     * @param type $alternativePart
     * @return boolean
     */
    private function removeAllContentPartsFromAlternative($contentType, $alternativePart)
    {
        $rmPart = $this->getContentPartContainerFromAlternative($contentType, $alternativePart);
        if ($rmPart === null) {
            return false;
        }
        $rmPart->removeAllParts();
        $this->removePart($rmPart);
        if ($alternativePart->getChildCount() === 1) {
            $this->replacePart($alternativePart, $alternativePart->getChild(0));
        }
        return true;
    }
    
    /**
     * Removes the content part of the message with the passed mime type.  If
     * there is a remaining content part and it is an alternative part of the
     * main message, the content part is moved to the message part.
     * 
     * If the content part is part of an alternative part beneath the message,
     * the alternative part is replaced by the remaining content part.
     * 
     * @param string $contentType
     * @return boolean true on success
     */
    protected function removeAllContentPartsByMimeType($mimeType)
    {
        $alt = $this->getPart(
            0,
            PartFilter::fromContentType('multipart/alternative')
        );
        if ($alt !== null) {
            return $this->removeAllContentPartsFromAlternative($mimeType, $alt);
        }
        $this->removeAllParts(new PartFilter([ 'headers' => 
            [ 
                PartFilter::FILTER_INCLUDE => [
                    'Content-Type' => $mimeType
                ],
                PartFilter::FILTER_EXCLUDE => [
                    'Content-Disposition' => 'attachment'
                ]
            ]
        ]));
    }
    
    /**
     * Removes the 'inline' part with the passed contentType, at the given index
     * defaulting to the first 
     * 
     * @param string $contentType
     * @return boolean true on success
     */
    protected function removePartByMimeType($mimeType, $index = 0)
    {
        $parts = $this->getAllParts(PartFilter::fromInlineContentType($mimeType));
        $alt = $this->getPart(
            0,
            PartFilter::fromInlineContentType('multipart/alternative')
        );
        if ($parts === null || !isset($parts[$index])) {
            return false;
        }
        $part = $parts[$index];
        $this->removePart($part);
        if ($alt !== null && $alt->getChildCount() === 1) {
            $this->replacePart($alt, $alt->getChild(0));
        }
        return true;
    }
    
    /**
     * Returns the text part (or null if none is set.)
     * 
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function getTextPart($index = 0)
    {
        return $this->getPart(
            $index,
            PartFilter::fromInlineContentType('text/plain')
        );
    }
    
    public function getTextPartCount()
    {
        $this->getPartCount(PartFilter::fromInlineContentType('text/plain'));
    }
    
    /**
     * Returns the HTML part (or null if none is set.)
     * 
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function getHtmlPart($index = 0)
    {
        return $this->getPart(
            $index,
            PartFilter::fromInlineContentType('text/html')
        );
    }
    
    public function getHtmlPartCount()
    {
        $this->getPartCount(PartFilter::fromInlineContentType('text/html'));
    }
    
    /**
     * Returns the content MimePart, which could be a text/plain, text/html or
     * multipart/alternative part or null if none is set.
     * 
     * This function is deprecated in favour of getTextPart/getHtmlPart and 
     * getPartByMimeType.
     * 
     * @deprecated since version 0.4.2
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function getContentPart()
    {
        $alternative = $this->getPart(0, PartFilter::fromContentType('multipart/alternative'));
        if ($alternative !== null) {
            return $alternative;
        }
        $text = $this->getTextPart();
        return ($text !== null) ? $text : $this->getHtmlPart();
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
     * Creates a unique mime boundary and assigns it to the passed part's
     * Content-Type header with the passed mime type.
     * 
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
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
     * another alternative content part.
     * 
     * Creates a content part and assigns the content stream from the message to
     * that newly created part.
     */
    private function setMessageAsAlternative()
    {
        $contentPart = $this->mimePartFactory->newMimePart();
        $contentPart->attachContentResourceHandle($this->handle);
        $this->detachContentResourceHandle();
        $this->removePart($this);
        $contentType = 'text/plain; charset="us-ascii"';
        $contentHeader = $this->getHeader('Content-Type');
        if ($contentHeader !== null) {
            $contentType = $contentHeader->getRawValue();
        }
        $contentPart->setRawHeader('Content-Type', $contentType);
        $contentPart->setParent($this);
        $this->setMimeHeaderBoundaryOnPart($this, 'multipart/alternative');
        $this->addPart($this);
        $this->addPart($contentPart, 0);
    }
    
    /**
     * Creates a new mime part as a multipart/alternative, assigning it to
     * $this->contentPart.  Adds the current contentPart below the newly created
     * alternative part.
     */
    private function createAlternativeContentPart($contentPart)
    {
        $altPart = $this->mimePartFactory->newMimePart();
        $this->setMimeHeaderBoundaryOnPart($altPart, 'multipart/alternative');
        $this->removePart($contentPart);
        $contentPart->setParent($altPart);
        $altPart->setParent($this);
        $this->addPart($altPart, 0);
        $this->addPart($contentPart, 0);
        return $altPart;
    }
    
    /**
     * Copies Content-Type, Content-Disposition and Content-Transfer-Encoding
     * headers from the $from header into the $to header. If the Content-Type
     * header isn't defined in $from, defaults to text/plain and
     * quoted-printable.
     * 
     * @param \ZBateson\MailMimeParser\Message\MimePart $from
     * @param \ZBateson\MailMimeParser\Message\MimePart $to
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
        $this->removePart($this);
        $this->setMimeHeaderBoundaryOnPart($this, 'multipart/mixed');
        $this->removeHeader('Content-Transfer-Encoding');
        $this->removeHeader('Content-Disposition');
        $this->addPart($this, 0);
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
        $messagePart->setParent($this);
        
        $this->copyTypeHeadersFromPartToPart($this, $messagePart);
        $messagePart->attachContentResourceHandle($this->handle);
        $this->detachContentResourceHandle();
        
        $this->addPart($messagePart, 0);
        foreach ($this->getChildParts() as $part) {
            if ($part === $messagePart) {
                continue;
            }
            $this->removePart($part);
            $part->setParent($messagePart);
            $this->addPart($part);
        }
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
            $signedPart->setParent($this);
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
        $alt = $this->getPart(
            0,
            PartFilter::fromContentType('multipart/alternative')
        );
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
            $this->removePart($this);
            $boundary = $this->getUniqueBoundary('multipart/signed');
            $this->setRawHeader(
                'Content-Type',
                "multipart/signed;\r\n\tboundary=\"$boundary\";\r\n\tmicalg=\"$micalg\"; protocol=\"$protocol\""
            );
            $this->removeHeader('Content-Disposition');
            $this->removeHeader('Content-Transfer-Encoding');
            $this->addPart($this);
        }
        $this->overwrite8bitContentEncoding();
        $this->ensureHtmlPartFirstForSignedMessage();
        $this->createSignaturePart('Not set');
    }
    
    /**
     * Returns the signed part or null if not set.
     * 
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function getSignaturePart()
    {
        if (strcasecmp($this->getHeaderValue('Content-Type'), 'multipart/signed') === 0) {
            return $this->getPart(0, new PartFilter([ 'signedpart' => PartFilter::FILTER_INCLUDE ]));
        }
        return null;
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
     * Creates a new content part for the passed mimeType and charset, making
     * space by creating a multipart/alternative if needed
     * 
     * @param string $mimeType
     * @param string $charset
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    private function createContentPartForMimeType($mimeType, $charset)
    {
        $mimePart = $this->mimePartFactory->newMimePart();
        $cset = ($charset === null) ? 'UTF-8' : $charset;
        $mimePart->setRawHeader('Content-Type', "$mimeType;\r\n\tcharset=\"$cset\"");
        $mimePart->setRawHeader('Content-Transfer-Encoding', 'quoted-printable');
        $this->enforceMime();
        
        $altType = ($mimeType === 'text/plain') ? 'text/html' : 'text/plain';
        $altPart = $this->getPart(
            0,
            PartFilter::fromInlineContentType($altType)
        );
        if ($altPart !== null && $altPart->getParent() !== null && $altPart->getParent()->isMultiPart()) {
            $altParent = $altPart->getParent();
            if ($altParent->getPartCount(PartFilter::fromDisposition('inline', PartFilter::FILTER_EXCLUDE)) !== $altParent->getChildCount()) {
                $newAltPart = $this->mimePartFactory->newMimePart();
                $newAltPart->setRawHeader('Content-Type', 'multipart/related');
                $newAltPart->setParent($altParent);
                $this->addPart($newAltPart, 0);
                foreach ($altParent->getAllParts(PartFilter::fromDisposition('inline')) as $part) {
                    $this->removePart($part);
                    $part->setParent($newAltPart);
                    $this->addPart($part);
                }
                $altPart = $newAltPart;
            } else {
                $altPart = $altParent;
            }
        }
        
        if ($altPart === $this) {
            $this->setMessageAsAlternative();
            $mimePart->setParent($this);
            $this->addPart($mimePart);
        } elseif ($altPart !== null) {
            $mimeAltPart = $this->createAlternativeContentPart($altPart);
            $mimePart->setParent($mimeAltPart);
            $this->addPart($mimePart, 1);
        } else {
            $mimePart->setParent($this);
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
     * Removes the text part of the message if one exists.  Returns true on
     * success.
     * 
     * @return bool true on success
     */
    public function removeTextPart($index = 0)
    {
        return $this->removePartByMimeType('text/plain', $index);
    }
    
    public function removeAllTextParts()
    {
        return $this->removeAllContentPartsByMimeType('text/plain');
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
    
    public function removeAllHtmlParts()
    {
        return $this->removeAllContentPartsByMimeType('text/html');
    }
    
    /**
     * Returns the non-content part at the given 0-based index, or null if none
     * is set.
     * 
     * @param int $index
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function getAttachmentPart($index)
    {
        $attachments = $this->getAllAttachmentParts();
        if (!isset($attachments[$index])) {
            return null;
        }
        return $attachments[$index];
    }
    
    /**
     * Returns all attachment parts.
     * 
     * @return \ZBateson\MailMimeParser\Message\MimePart[]
     */
    public function getAllAttachmentParts()
    {
        $parts = $this->getAllParts(
            new PartFilter([
                'multipart' => PartFilter::FILTER_EXCLUDE
            ])
        );
        return array_values(array_filter(
            $parts,
            function ($part) {
                return !(
                    $part->isTextPart()
                    && $part->getHeaderValue('Content-Disposition', 'inline') === 'inline'
                );
            }
        ));
    }
    
    /**
     * Returns the number of attachments available.
     * 
     * @return int
     */
    public function getAttachmentCount()
    {
        return count($this->getAllAttachmentParts());
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
     * @return \ZBateson\MailMimeParser\Message\MimePart
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
        $part->setParent($this);
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
        $part->setParent($this);
        $part->attachContentResourceHandle($handle);
        $this->addPart($part);
    }
    
    /**
     * Returns a resource handle where the text content can be read or null if
     * unavailable.
     * 
     * @return resource
     */
    public function getTextStream($index = 0)
    {
        $textPart = $this->getTextPart($index);
        if ($textPart !== null) {
            return $textPart->getContentResourceHandle();
        }
        return null;
    }
    
    /**
     * Returns the text content as a string.
     * 
     * Reads the entire stream content into a string and returns it.  Returns
     * null if the message doesn't have a text part.
     * 
     * @return string
     */
    public function getTextContent($index = 0)
    {
        $part = $this->getTextPart($index);
        if ($part !== null) {
            return $part->getContent();
        }
        return null;
    }
    
    /**
     * Returns a resource handle where the HTML content can be read or null if
     * unavailable.
     * 
     * @return resource
     */
    public function getHtmlStream($index = 0)
    {
        $htmlPart = $this->getHtmlPart($index);
        if ($htmlPart !== null) {
            return $htmlPart->getContentResourceHandle();
        }
        return null;
    }
    
    /**
     * Returns the HTML content as a string.
     * 
     * Reads the entire stream content into a string and returns it.  Returns
     * null if the message doesn't have an HTML part.
     * 
     * @return string
     */
    public function getHtmlContent($index = 0)
    {
        $part = $this->getHtmlPart($index);
        if ($part !== null) {
            return $part->getContent();
        }
        return null;
    }
    
    /**
     * Returns true if either a Content-Type or Mime-Version header are defined
     * in this Message.
     * 
     * @return bool
     */
    public function isMime()
    {
        $contentType = $this->getHeaderValue('Content-Type');
        $mimeVersion = $this->getHeaderValue('Mime-Version');
        return ($contentType !== null || $mimeVersion !== null);
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
    
    /**
     * Shortcut to call Message::save with a php://temp stream and return the
     * written email message as a string.
     * 
     * @return string
     */
    public function __toString()
    {
        $handle = fopen('php://temp', 'r+');
        $this->save($handle);
        rewind($handle);
        $str = stream_get_contents($handle);
        fclose($handle);
        return $str;
    }
}
