<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ArrayIterator;
use Iterator;

/**
 * A parsed mime message with optional mime parts depending on its type.
 * 
 * A mime message may have any number of mime parts, and each part may have any
 * number of sub-parts, etc...
 * 
 * A message is a specialized "mime part". Namely the message keeps hold of text
 * versus HTML parts (and associated streams for easy access), holds a stream
 * for the entire message and all its parts, and maintains parts and their
 * relationships.
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
     * @var \ZBateson\MailMimeParser\MimePart represents the content portion of
     *      the email message.  It is assigned either a text or HTML part, or a
     *      MultipartAlternativePart
     */
    protected $contentPart;
    
    /**
     * @var \ZBateson\MailMimeParser\MimePart contains the body of the signature
     *      for a multipart/signed message.
     */
    protected $signedSignaturePart;
    
    /**
     * @var \ZBateson\MailMimeParser\MimePart The mixed part for a
     *      multipart/signed message if the message contains attachments
     */
    protected $signedMixedPart;
    
    /**
     * @var \ZBateson\MailMimeParser\MimePart[] array of non-content parts in
     *      this message 
     */
    protected $attachmentParts = [];
    
    /**
     * @var \ZBateson\MailMimeParser\MimePartFactory a MimePartFactory to create
     *      parts for attachments/content
     */
    protected $mimePartFactory;
    
    /**
     * @var bool set to true if a newline should be inserted before the next
     *      boundary (signed messages are finicky)
     */
    private $insertNewLineBeforeBoundary = false;
    
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
     * @param MimePartFactory $mimePartFactory
     */
    public function __construct(HeaderFactory $headerFactory, MimePartFactory $mimePartFactory)
    {
        parent::__construct($headerFactory);
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
    
    /**
     * Loops through the parts parents to find if it's an alternative part or
     * an attachment.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     * @return boolean true if its been added
     */
    private function addToAlternativeContentPartFromParsed(MimePart $part)
    {
        $partType = strtolower($this->contentPart->getHeaderValue('Content-Type', 'text/plain'));
        if ($partType === 'multipart/alternative') {
            if ($this->contentPart === $this) {
                // already added in addPart
                return true;
            }
            $parent = $part->getParent();
            while ($parent !== null) {
                if ($parent === $this->contentPart) {
                    $parent->addPart($part);
                    return true;
                }
                $parent = $parent->getParent();
            }
        }
        return false;
    }
    
    /**
     * Returns true if the $part should be assigned as this message's main
     * content part.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     * @return bool
     */
    private function addContentPartFromParsed(MimePart $part)
    {
        $type = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
        // separate if statements for clarity
        if (!empty($this->contentPart)) {
            return $this->addToAlternativeContentPartFromParsed($part);
        }
        if ($type === 'multipart/alternative'
            || $type === 'text/plain'
            || $type === 'text/html') {
            $this->contentPart = $part;
            return true;
        }
        return false;
    }
    
    /**
     * Either adds the passed part to $this->textPart if its content type is
     * text/plain, to $this->htmlPart if it's text/html, or adds the part to the
     * parts array otherwise.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     */
    public function addPart(MimePart $part)
    {
        parent::addPart($part);
        $disposition = $part->getHeaderValue('Content-Disposition');
        $mtype = $this->getHeaderValue('Content-Type');
        $protocol = $this->getHeaderParameter('Content-Type', 'protocol');
        $type = $part->getHeaderValue('Content-Type');
        if (strcasecmp($mtype, 'multipart/signed') === 0 && $protocol !== null && $part->getParent() === $this && strcasecmp($protocol, $type) === 0) {
            $this->signedSignaturePart = $part;
            $this->createMultipartMixedForSignedMessage();
        } else if ((!empty($disposition) || !$this->addContentPartFromParsed($part)) && !$part->isMultiPart()) {
            $this->attachmentParts[] = $part;
        }
    }
    
    /**
     * Returns the content part (or null) for the passed mime type looking at
     * the assigned content part, and if it's a multipart/alternative part,
     * looking to find an alternative part of the passed mime type.
     * 
     * @param string $mimeType
     * @return \ZBateson\MailMimeParser\MimePart or null if not available
     */
    protected function getContentPartByMimeType($mimeType)
    {
        if (!isset($this->contentPart)) {
            return null;
        }
        $type = strtolower($this->contentPart->getHeaderValue('Content-Type', 'text/plain'));
        if ($type === 'multipart/alternative') {
            return $this->contentPart->getPartByMimeType($mimeType);
        } elseif ($type === $mimeType) {
            return $this->contentPart;
        }
        return null;
    }
    
    /**
     * Sets the content of the message to the content of the passed part, for a
     * message with a multipart/alternative content type where the other part
     * has been removed, and this is the only remaining part.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     */
    private function overrideAlternativeMessageContentFromContentPart(MimePart $part)
    {
        $contentType = $part->getHeaderValue('Content-Type');
        if ($contentType === null) {
            $contentType = 'text/plain; charset="us-ascii"';
        }
        $this->setRawHeader(
            'Content-Type',
            $contentType
        );
        $this->setRawHeader(
            'Content-Transfer-Encoding',
            'quoted-printable'
        );
        $this->attachContentResourceHandle($part->getContentResourceHandle());
        $part->detachContentResourceHandle();
        $this->removePart($part);
        $this->removePart($this);
        $this->addPart($this);
    }
    
    /**
     * Removes the passed MimePart as a content part.  If there's a remaining
     * part, either sets the content on this message if the message itself is a
     * multipart/alternative message, or overrides the contentPart with the
     * remaining part.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     */
    private function removePartFromAlternativeContentPart(MimePart $part)
    {
        $this->removePart($part);
        $this->contentPart->removePart($part);
        if ($this->contentPart === $this) {
            $this->overrideAlternativeMessageContentFromContentPart($this->getPart(1));
        } elseif ($this->contentPart->getPartCount() === 1) {
            $this->removePart($this->contentPart);
            $this->contentPart = $this->contentPart->getPart(0);
            $this->contentPart->setParent($this);
        }
    }
    
    /**
     * Loops over children of the content part looking for a part with the
     * passed mime type, then proceeds to remove it by calling
     * removePartFromAlternativeContentPart.
     * 
     * @param string $contentType
     * @return boolean true on success
     */
    private function removeContentPartFromAlternative($contentType)
    {
        $parts = $this->contentPart->getAllParts();
        foreach ($parts as $part) {
            $type = $part->getHeaderValue('Content-Type', 'text/plain');
            if (strcasecmp($type, $contentType) === 0) {
                $this->removePartFromAlternativeContentPart($part);
                return true;
            }
        }
        return false;
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
    protected function removeContentPart($contentType)
    {
        if (!isset($this->contentPart)) {
            return false;
        }
        $type = $this->contentPart->getHeaderValue('Content-Type', 'text/plain');
        if (strcasecmp($type, $contentType) === 0) {
            if ($this->contentPart === $this) {
                return false;
            }
            $this->removePart($this->contentPart);
            $this->contentPart = null;
            return true;
        }
        return $this->removeContentPartFromAlternative($contentType);
    }
    
    /**
     * Returns the text part (or null if none is set.)
     * 
     * @return \ZBateson\MailMimeParser\MimePart
     */
    public function getTextPart()
    {
        return $this->getContentPartByMimeType('text/plain');
    }
    
    /**
     * Returns the HTML part (or null if none is set.)
     * 
     * @return \ZBateson\MailMimeParser\MimePart
     */
    public function getHtmlPart()
    {
        return $this->getContentPartByMimeType('text/html');
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
     * @return string
     */
    private function getUniqueBoundary()
    {
        return uniqid('----=MMP-' . $this->objectId . '.', true);
    }
    
    /**
     * Creates a unique mime boundary and assigns it to the passed part's
     * Content-Type header with the passed mime type.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     * @param string $mimeType
     */
    private function setMimeHeaderBoundaryOnPart(MimePart $part, $mimeType)
    {
        $part->setRawHeader(
            'Content-Type',
            "$mimeType;\r\n\tboundary=\"" 
                . $this->getUniqueBoundary() . "\""
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
        $contentType = 'text/plain; charset="us-ascii"';
        $contentHeader = $this->getHeader('Content-Type');
        if ($contentHeader !== null) {
            $contentType = $contentHeader->getRawValue();
        }
        $contentPart->setRawHeader('Content-Type', $contentType);
        $contentPart->setParent($this);
        $this->setMimeHeaderBoundaryOnPart($this, 'multipart/alternative');
        parent::addPart($contentPart);
    }
    
    /**
     * Creates a new mime part as a multipart/alternative, assigning it to
     * $this->contentPart.  Adds the current contentPart below the newly created
     * alternative part.
     */
    private function createAlternativeContentPart()
    {
        $altPart = $this->mimePartFactory->newMimePart();
        $this->setMimeHeaderBoundaryOnPart($altPart, 'multipart/alternative');
        $this->contentPart->setParent($altPart);
        $altPart->addPart($this->contentPart);
        $this->contentPart = $altPart;
        $altPart->setParent($this);
        parent::addPart($altPart);
    }
    
    /**
     * Copies Content-Type and Content-Transfer-Encoding headers from the $from
     * header into the $to header. If the Content-Type header isn't defined in
     * $from, defaults to text/plain and quoted-printable.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $from
     * @param \ZBateson\MailMimeParser\MimePart $to
     */
    private function copyTypeHeadersFromPartToPart(MimePart $from, MimePart $to)
    {
        $typeHeader = $from->getHeader('Content-Type');
        if (!empty($typeHeader)) {
            $to->setRawHeader('Content-Type', $typeHeader->getRawValue());
            $encodingHeader = $from->getHeader('Content-Transfer-Encoding');
            if (!empty($encodingHeader)) {
                $to->setRawHeader('Content-Transfer-Encoding', $encodingHeader->getRawValue());
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
        $part = $this->createNewContentPartFromPart($this->contentPart);
        $this->removePart($this->contentPart);
        parent::addPart($part);
        $this->contentPart = $part;
        $this->setMimeHeaderBoundaryOnPart($this, 'multipart/mixed');
    }
    
    /**
     * Updates parents of the contentPart and any children, and sets
     * $this->contentPart to the passed $messagePart if the $this->contentPart
     * is set to $this
     * 
     * @param \ZBateson\MailMimeParser\MimePart $messagePart
     */
    private function updateContentPartForSignedMessage(MimePart $messagePart)
    {
        if ($this->contentPart === $this) {
            $this->contentPart = $messagePart;
            foreach ($this->getAllParts() as $child) {
                if ($child === $this) {
                    continue;
                }
                $child->setParent($messagePart);
                array_unshift($this->contentPart->parts, $child);
            }
        } elseif ($this->contentPart->getParent() === $this) {
            $this->contentPart->setParent($messagePart);
        }
    }
    
    /**
     * This function makes space by moving the main message part down one level.
     * 
     * The content-type and content-transfer-encoding headers are copied from
     * this message to the newly created part, the resource handle is moved and
     * detached, any attachments and content parts with parents set to this
     * message get their parents set to the newly created part.
     */
    private function makeSpaceForMultipartSignedMessage()
    {
        $this->enforceMime();
        $messagePart = $this->mimePartFactory->newMimePart();
        $this->updateContentPartForSignedMessage($messagePart);
        $this->copyTypeHeadersFromPartToPart($this, $messagePart);
        $messagePart->attachContentResourceHandle($this->handle);
        $this->detachContentResourceHandle();
        $messagePart->setParent($this);
        foreach ($this->attachmentParts as $part) {
            if ($part->getParent() === $this) {
                $part->setParent($messagePart);
            }
        }
        array_unshift($this->parts, $messagePart);
    }
    
    /**
     * Creates and returns a new MimePart for the signature part of a
     * multipart/signed message and assigns it to $this->signedSignaturePart.
     * 
     * @param string $protocol
     * @param string $body
     */
    public function createSignaturePart($body)
    {
        $signedPart = $this->mimePartFactory->newMimePart();
        $signedPart->setRawHeader(
            'Content-Type',
            $this->getHeaderParameter('Content-Type', 'protocol')
        );
        $signedPart->setContent($body);
        $this->parts[] = $signedPart;
        $signedPart->setParent($this);
        $this->signedSignaturePart = $signedPart;
    }
    
    /**
     * Creates a multipart/mixed MimePart assigns it to $this->signedMixedPart
     * if the message contains attachments.
     * 
     * @param array $parts
     */
    private function createMultipartMixedForSignedMessage()
    {
        if (count($this->attachmentParts) === 0 || $this->signedMixedPart !== null) {
            return;
        }
        $mixed = $this->mimePartFactory->newMimePart();
        $mixed->setParent($this);
        $boundary = $this->getUniqueBoundary();
        $mixed->setRawHeader('Content-Type', "multipart/mixed;\r\n\tboundary=\"$boundary\"");
        foreach ($this->attachmentParts as $part) {
            $part->setParent($mixed);
        }
        if (!empty($this->contentPart)) {
            $this->contentPart->setParent($mixed);
        }
        $this->signedMixedPart = $mixed;
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
        foreach ($this->parts as $part) {
            if ($part->getHeaderValue('Content-Transfer-Encoding') === '8bit') {
                if (preg_match('/text\/.*/', $part->getHeaderValue('Content-Type'))) {
                    $part->setRawHeader('Content-Transfer-Encoding', 'quoted-printable');
                } else {
                    $part->setRawHeader('Content-Transfer-Encoding', 'base64');
                }
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
        if (empty($this->contentPart)) {
            return;
        }
        $type = strtolower($this->contentPart->getHeaderValue('Content-Type', 'text/plain'));
        if ($type === 'multipart/alternative' && count($this->contentPart->parts) > 1) {
            if (strtolower($this->contentPart->parts[0]->getHeaderValue('Content-Type', 'text/plain')) === 'text/plain') {
                $tmp = $this->contentPart->parts[0];
                $this->contentPart->parts[0] = $this->contentPart->parts[1];
                $this->contentPart->parts[1] = $tmp;
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
     * @param string $body The signature signed according to the value of
     *        $protocol
     */
    public function setAsMultipartSigned($micalg, $protocol)
    {
        $contentType = $this->getHeaderValue('Content-Type', 'text/plain');
        if (strcasecmp($contentType, 'multipart/signed') !== 0 && strcasecmp($contentType,'multipart/mixed') !== 0) {
            $this->makeSpaceForMultipartSignedMessage();
        }
        $boundary = $this->getUniqueBoundary();
        $this->setRawHeader(
            'Content-Type',
            "multipart/signed;\r\n\tboundary=\"$boundary\";\r\n\tmicalg=\"$micalg\"; protocol=\"$protocol\""
        );
        $this->removeHeader('Content-Transfer-Encoding');
        $this->createMultipartMixedForSignedMessage();
        $this->overwrite8bitContentEncoding();
        $this->ensureHtmlPartFirstForSignedMessage();
        $this->createSignaturePart('Not set');
    }
    
    /**
     * Returns the signed part or null if not set.
     * 
     * @return \ZBateson\MailMimeParser\MimePart
     */
    public function getSignaturePart()
    {
        return $this->signedSignaturePart;
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
     * @return \ZBateson\MailMimeParser\MimePart
     */
    private function createContentPartForMimeType($mimeType, $charset)
    {
        // wouldn't come here unless there's only one 'content part' anyway
        // if this->contentPart === $this, then $this is not a multipart/alternative
        // message
        $mimePart = $this->mimePartFactory->newMimePart();
        $cset = ($charset === null) ? 'UTF-8' : $charset;
        $mimePart->setRawHeader('Content-Type', "$mimeType;\r\n\tcharset=\"$cset\"");
        $mimePart->setRawHeader('Content-Transfer-Encoding', 'quoted-printable');
        $this->enforceMime();
        if ($this->contentPart === $this) {
            $this->setMessageAsAlternative();
            $mimePart->setParent($this->contentPart);
            parent::addPart($mimePart);
        } elseif ($this->contentPart !== null) {
            $this->createAlternativeContentPart();
            $mimePart->setParent($this->contentPart);
            $this->contentPart->addPart($mimePart);
        } else {
            $this->contentPart = $mimePart;
            $mimePart->setParent($this);
            parent::addPart($mimePart);
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
        $part = $this->getTextPart();
        if ($mimeType === 'text/html') {
            $part = $this->getHtmlPart();
        }
        $handle = $this->getHandleForStringOrHandle($stringOrHandle);
        if ($part === null) {
            $part = $this->createContentPartForMimeType($mimeType, $charset);
        } elseif ($charset !== null) {
            $cset = ($charset === null) ? 'UTF-8' : $charset;
            $contentType = $part->getHeaderValue('Content-Type', 'text/plain');
            $part->setRawHeader('Content-Type', "$contentType;\r\n\tcharset=\"$cset\"");
        }
        $part->attachContentResourceHandle($handle);
    }
    
    /**
     * Sets the text/plain part of the message to the passed $stringOrHandle,
     * either creating a new part if one doesn't exist for text/plain, or
     * assigning the value of $stringOrHandle to an existing text/plain part.
     * 
     * The optional $charset parameter is the charset for saving to.
     * $stringOrHandle is expected to be in UTF-8.
     * 
     * @param string|resource $stringOrHandle
     * @param string $charset
     */
    public function setTextPart($stringOrHandle, $charset = null)
    {
        $this->setContentPartForMimeType('text/plain', $stringOrHandle, $charset);
    }
    
    /**
     * Sets the text/html part of the message to the passed $stringOrHandle,
     * either creating a new part if one doesn't exist for text/html, or
     * assigning the value of $stringOrHandle to an existing text/html part.
     * 
     * The optional $charset parameter is the charset for saving to.
     * $stringOrHandle is expected to be in UTF-8.
     * 
     * @param string|resource $stringOrHandle
     * @param string $charset
     */
    public function setHtmlPart($stringOrHandle, $charset = null)
    {
        $this->setContentPartForMimeType('text/html', $stringOrHandle, $charset);
    }
    
    /**
     * Removes the text part of the message if one exists.  Returns true on
     * success.
     * 
     * @return bool true on success
     */
    public function removeTextPart()
    {
        return $this->removeContentPart('text/plain');
    }
    
    /**
     * Removes the html part of the message if one exists.  Returns true on
     * success.
     * 
     * @return bool true on success
     */
    public function removeHtmlPart()
    {
        return $this->removeContentPart('text/html');
    }
    
    /**
     * Returns the non-content part at the given 0-based index, or null if none
     * is set.
     * 
     * @param int $index
     * @return \ZBateson\MailMimeParser\MimePart
     */
    public function getAttachmentPart($index)
    {
        if (!isset($this->attachmentParts[$index])) {
            return null;
        }
        return $this->attachmentParts[$index];
    }
    
    /**
     * Returns all attachment parts.
     * 
     * @return \ZBateson\MailMimeParser\MimePart[]
     */
    public function getAllAttachmentParts()
    {
        return $this->attachmentParts;
    }
    
    /**
     * Returns the number of attachments available.
     * 
     * @return int
     */
    public function getAttachmentCount()
    {
        return count($this->attachmentParts);
    }
    
    /**
     * Removes the attachment with the given index
     * 
     * @param int $index
     */
    public function removeAttachmentPart($index)
    {
        $part = $this->attachmentParts[$index];
        $this->removePart($part);
        array_splice($this->attachmentParts, $index, 1);
    }
    
    /**
     * Creates and returns a MimePart for use with a new attachment part being
     * created.
     * 
     * @return \ZBateson\MailMimeParser\MimePart
     */
    protected function createPartForAttachment()
    {
        $part = null;
        if ($this->isMime()) {
            $part = $this->mimePartFactory->newMimePart();
            $part->setRawHeader('Content-Transfer-Encoding', 'base64');
            if ($this->getHeaderValue('Content-Type') !== 'multipart/mixed') {
                $this->setMessageAsMixed();
            }
        } else {
            $part = $this->mimePartFactory->newUUEncodedPart();
        }
        return $part;
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
        $this->parts[] = $part;
        $this->attachmentParts[] = $part;
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
        $this->parts[] = $part;
        $this->attachmentParts[] = $part;
    }
    
    /**
     * Returns a resource handle where the text content can be read or null if
     * unavailable.
     * 
     * @return resource
     */
    public function getTextStream()
    {
        $textPart = $this->getTextPart();
        if (!empty($textPart)) {
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
    public function getTextContent()
    {
        $stream = $this->getTextStream();
        if ($stream === null) {
            return null;
        }
        return stream_get_contents($stream);
    }
    
    /**
     * Returns a resource handle where the HTML content can be read or null if
     * unavailable.
     * 
     * @return resource
     */
    public function getHtmlStream()
    {
        $htmlPart = $this->getHtmlPart();
        if (!empty($htmlPart)) {
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
    public function getHtmlContent()
    {
        $stream = $this->getHtmlStream();
        if ($stream === null) {
            return null;
        }
        return stream_get_contents($stream);
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
     * Writes out a mime boundary to the passed $handle
     * 
     * @param resource $handle
     * @param string $boundary
     * @param bool $isEnd
     */
    private function writeBoundary($handle, $boundary, $isEnd = false)
    {
        if ($this->insertNewLineBeforeBoundary) {
            fwrite($handle, "\r\n");
        }
        fwrite($handle, '--');
        fwrite($handle, $boundary);
        if ($isEnd) {
            fwrite($handle, "--\r\n");
        } else {
            fwrite($handle, "\r\n");
        }
        $this->insertNewLineBeforeBoundary = $isEnd;
    }
    
    /**
     * Writes out any necessary boundaries for the given $part if required based
     * on its $parent and $boundaryParent.
     * 
     * Also writes out end boundaries for the previous part if applicable.
     * 
     * @param resource $handle
     * @param \ZBateson\MailMimeParser\MimePart $part
     * @param \ZBateson\MailMimeParser\MimePart $parent
     * @param \ZBateson\MailMimeParser\MimePart $boundaryParent
     * @param string $boundary
     */
    private function writePartBoundaries($handle, MimePart $part, MimePart $parent, MimePart &$boundaryParent, $boundary)
    {
        if ($boundaryParent !== $parent && $boundaryParent !== $part) {
            if ($boundaryParent !== null && $parent->getParent() !== $boundaryParent) {
                $this->writeBoundary($handle, $boundary, true);
            }
            $boundaryParent = $parent;
            $boundary = $boundaryParent->getHeaderParameter('Content-Type', 'boundary');
        }
        if ($boundaryParent !== null && $boundaryParent !== $part) {
            $this->writeBoundary($handle, $boundary);
        }
    }
    
    /**
     * Writes out the passed mime part, writing out any necessary mime
     * boundaries.
     * 
     * @param resource $handle
     * @param \ZBateson\MailMimeParser\MimePart $part
     * @param \ZBateson\MailMimeParser\MimePart $parent
     * @param \ZBateson\MailMimeParser\MimePart $boundaryParent
     */
    private function writePartTo($handle, MimePart $part, MimePart $parent, MimePart &$boundaryParent)
    {
        $boundary = $boundaryParent->getHeaderParameter('Content-Type', 'boundary');
        if (!empty($boundary)) {
            $this->writePartBoundaries($handle, $part, $parent, $boundaryParent, $boundary);
            if ($part !== $this) {
                $part->writeTo($handle);
            } else {
                $part->writeContentTo($handle);
            }
        } elseif ($part instanceof NonMimePart) {
            fwrite($handle, "\r\n\r\n");
            $part->writeContentTo($handle);
        } else {
            $part->writeContentTo($handle);
        }
        $this->insertNewLineBeforeBoundary = $part->hasContent();
    }
    
    /**
     * Either returns $this for a non-text, non-html part, or returns
     * $this->contentPart.
     * 
     * Note that if Content-Disposition is set on the passed part, $this is
     * always returned.
     * 
     * @param \ZBateson\MailMimeParser\MimePart $part
     * @return \ZBateson\MailMimeParser\MimePart
     */
    private function getWriteParentForPart(MimePart $part)
    {
        $type = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
        $disposition = $part->getHeaderValue('Content-Disposition');
        if (empty($disposition) && $this->contentPart !== $part && ($type === 'text/html' || $type === 'text/plain')) {
            return $this->contentPart;
        } elseif ($this->signedSignaturePart !== null) {
            return $part->getParent();
        }
        return $this;
    }
    
    /**
     * Loops over parts of the message and writes them as an email to the
     * provided $handle.
     * 
     * The function rewrites mime parts in a multipart-mime message to be either
     * alternatives of text/plain and text/html, or attachments because
     * MailMimeParser doesn't currently maintain the structure of the original
     * message.  This means other alternative parts would be dropped to
     * attachments, and multipart/related parts are completely ignored.
     * 
     * @param resource $handle the handle to write out to
     * @param Iterator $partsIter an Iterator for parts to save
     * @param \ZBateson\MailMimeParser\MimePart $curParent the current parent
     */
    protected function writePartsTo($handle, Iterator $partsIter, MimePart $curParent)
    {
        $this->insertNewLineBeforeBoundary = false;
        $boundary = $curParent->getHeaderParameter('Content-Type', 'boundary');
        while ($partsIter->valid()) {
            $part = $partsIter->current();
            $parent = $this->getWriteParentForPart($part);
            $this->writePartTo($handle, $part, $parent, $curParent);
            $partsIter->next();
        }
        if (!empty($boundary)) {
            $this->writeBoundary($handle, $boundary, true);
        }
    }
    
    /**
     * Saves the message as a MIME message to the passed resource handle.
     * 
     * The saved message is not guaranteed to be the same as the parsed message.
     * Namely, for mime messages anything that is not text/html or text/plain
     * will be moved into parts under the main 'message' as attachments, other
     * alternative parts are dropped, and multipart/related parts are ignored
     * (their contents are either moved under a multipart/alternative part or as
     * attachments below the main multipart/mixed message).
     * 
     * @param resource $handle
     */
    public function save($handle)
    {
        $this->writeHeadersTo($handle);
        $parts = [];
        if (!empty($this->signedMixedPart)) {
            $parts[] = $this->signedMixedPart;
        }
        if ($this->contentPart !== null) {
            if ($this->contentPart->isMultiPart()) {
                $parts[] = $this->contentPart;
                $parts = array_merge($parts, $this->contentPart->getAllParts());
            } else {
                $parts[] = $this->contentPart;
            }
        }
        if (!empty($this->attachmentParts)) {
            $parts = array_merge($parts, $this->attachmentParts);
        }
        if (!empty($this->signedSignaturePart)) {
            $parts[] = $this->signedSignaturePart;
        }
        $this->writePartsTo(
            $handle,
            new ArrayIterator($parts),
            $this
        );
    }
    
    /**
     * Writes out the content of the message into a string and returns it.
     * 
     * @return string
     */
    private function getSignableBodyFromParts(array $parts)
    {
        $handle = fopen('php://temp', 'r+');
        $firstPart = array_shift($parts);
        $firstPart->writeHeadersTo($handle);
        $firstPart->writeContentTo($handle);
        if (!empty($parts)) {
            $this->writePartsTo(
                $handle,
                new ArrayIterator($parts),
                $firstPart
            );
        }
        rewind($handle);
        $str = stream_get_contents($handle);
        fclose($handle);
        return $str;
    }
    
    /**
     * Returns the content part of a signed message for a signature to be
     * calculated on the message.
     * 
     * @return string
     */
    public function getSignableBody()
    {
        $parts = [];
        if (!empty($this->signedMixedPart)) {
            $parts[] = $this->signedMixedPart;
        }
        if ($this->contentPart !== null) {
            if ($this->contentPart->isMultiPart()) {
                $parts[] = $this->contentPart;
                $parts = array_merge($parts, $this->contentPart->getAllParts());
            } else {
                $parts[] = $this->contentPart;
            }
        }
        if (!empty($this->attachmentParts)) {
            $parts = array_merge($parts, $this->attachmentParts);
        }
        return $this->getSignableBodyFromParts($parts);
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
