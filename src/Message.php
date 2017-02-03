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
     * @var \ZBateson\MailMimeParser\Message\MimePart represents the content portion of
     *      the email message.  It is assigned either a text or HTML part, or a
     *      MultipartAlternativePart
     */
    protected $contentPart;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\MimePart contains the body of the signature
     *      for a multipart/signed message.
     */
    protected $signedSignaturePart;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\MimePart[] array of non-content parts in
     *      this message 
     */
    protected $attachmentParts = [];
    
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

    /**
     * Returns true if the $part should be assigned as this message's main
     * content part.
     * 
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     * @return bool
     */
    private function addContentPartFromParsed(MimePart $part)
    {
        $type = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
        // separate if statements for clarity
        if ($type === 'multipart/alternative'
            || $type === 'text/plain'
            || $type === 'text/html') {
            if ($this->contentPart === null) {
                $this->contentPart = $part;
            }
            return true;
        }
        return false;
    }
    
    /**
     * Adds the passed part to the message with the passed position, or at the
     * end if not passed.
     * 
     * This should not be used by a user directly and will be set 'protected' in
     * the future.  Instead setTextPart, setHtmlPart and addAttachment should be
     * used.
     * 
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     * @param int $position
     */
    public function addPart(MimePart $part, $position = null)
    {
        parent::addPart($part, $position);
        $disposition = $part->getHeaderValue('Content-Disposition');
        $mtype = $this->getHeaderValue('Content-Type');
        $protocol = $this->getHeaderParameter('Content-Type', 'protocol');
        $type = $part->getHeaderValue('Content-Type');
        if (strcasecmp($mtype, 'multipart/signed') === 0 && $protocol !== null && $part->getParent() === $this && strcasecmp($protocol, $type) === 0) {
            $this->signedSignaturePart = $part;
        } else if (($disposition !== null || !$this->addContentPartFromParsed($part)) && !$part->isMultiPart()) {
            $this->attachmentParts[] = $part;
        }
    }
    
    /**
     * Returns the content part (or null) for the passed mime type looking at
     * the assigned content part, and if it's a multipart/alternative part,
     * looking to find an alternative part of the passed mime type.
     * 
     * @param string $mimeType
     * @return \ZBateson\MailMimeParser\Message\MimePart or null if not
     *         available
     */
    protected function getContentPartByMimeType($mimeType)
    {
        if (!isset($this->contentPart)) {
            return null;
        }
        $type = strtolower($this->contentPart->getHeaderValue('Content-Type', 'text/plain'));
        if ($type === 'multipart/alternative') {
            return $this->getPartByMimeType($mimeType);
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
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
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
    }
    
    /**
     * Removes the passed MimePart as a content part.  If there's a remaining
     * part, either sets the content on this message if the message itself is a
     * multipart/alternative message, or overrides the contentPart with the
     * remaining part.
     * 
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     */
    private function removePartFromAlternativeContentPart(MimePart $part)
    {
        $this->removePart($part);
        $contentPart = $this->contentPart->getPart(0);
        if ($contentPart !== null) {
            if ($this->contentPart === $this) {
                $this->overrideAlternativeMessageContentFromContentPart($contentPart);
            } elseif ($this->contentPart->getPartCount() === 1) {
                $this->removePart($this->contentPart);
                $contentPart->setParent($this);
                $this->contentPart = null;
                $this->addPart($contentPart, 0);
            }
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
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function getTextPart()
    {
        return $this->getContentPartByMimeType('text/plain');
    }
    
    /**
     * Returns the HTML part (or null if none is set.)
     * 
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function getHtmlPart()
    {
        return $this->getContentPartByMimeType('text/html');
    }
    
    /**
     * Returns the content MimePart, which could be a text/plain, text/html or
     * multipart/alternative part or null if none is set.
     * 
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    public function getContentPart()
    {
        return $this->contentPart;
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
        $this->contentPart = null;
        $this->addPart($this);
        $this->addPart($contentPart, 0);
    }
    
    /**
     * Creates a new mime part as a multipart/alternative, assigning it to
     * $this->contentPart.  Adds the current contentPart below the newly created
     * alternative part.
     */
    private function createAlternativeContentPart()
    {
        $altPart = $this->mimePartFactory->newMimePart();
        $contentPart = $this->contentPart;
        $this->setMimeHeaderBoundaryOnPart($altPart, 'multipart/alternative');
        $this->removePart($contentPart);
        $contentPart->setParent($altPart);
        $this->contentPart = null;
        $altPart->setParent($this);
        $this->addPart($altPart, 0);
        $this->addPart($contentPart, 0);
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
        $part = $this->createNewContentPartFromPart($this->contentPart);
        $this->removePart($this->contentPart);
        $this->contentPart = null;
        $this->addPart($part, 0);
        $this->setMimeHeaderBoundaryOnPart($this, 'multipart/mixed');
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
        
        $this->contentPart = null;
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
     * multipart/signed message and assigns it to $this->signedSignaturePart.
     * 
     * @param string $body
     */
    public function createSignaturePart($body)
    {
        $signedPart = $this->signedSignaturePart;
        if ($signedPart === null) {
            $signedPart = $this->mimePartFactory->newMimePart();
            $signedPart->setParent($this);
            $this->addPart($signedPart);
            $this->signedSignaturePart = $signedPart;
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
        $parts = array_merge([ $this ], $this->getAllParts());
        foreach ($parts as $part) {
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
        if ($this->contentPart === null) {
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
     */
    public function setAsMultipartSigned($micalg, $protocol)
    {
        $contentType = $this->getHeaderValue('Content-Type', 'text/plain');
        if (strcasecmp($contentType, 'multipart/signed') !== 0) {
            $this->makeSpaceForMultipartSignedMessage();
        }
        $boundary = $this->getUniqueBoundary('multipart/signed');
        $this->setRawHeader(
            'Content-Type',
            "multipart/signed;\r\n\tboundary=\"$boundary\";\r\n\tmicalg=\"$micalg\"; protocol=\"$protocol\""
        );
        $this->removeHeader('Content-Transfer-Encoding');
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
     * @return \ZBateson\MailMimeParser\Message\MimePart
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
            $this->addPart($mimePart, 0);
        } elseif ($this->contentPart !== null) {
            $this->createAlternativeContentPart();
            $mimePart->setParent($this->contentPart);
            $this->addPart($mimePart, 0);
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
     * @return \ZBateson\MailMimeParser\Message\MimePart
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
     * @return \ZBateson\MailMimeParser\Message\MimePart[]
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
    public function getTextStream()
    {
        $textPart = $this->getTextPart();
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
