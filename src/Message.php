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
     * @var \ZBateson\MailMimeParser\MimePart[] array of non-content parts in
     *      this message 
     */
    protected $attachmentParts = [];
    
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
     */
    public function __construct(HeaderFactory $headerFactory)
    {
        parent::__construct($headerFactory);
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
        $partType = $this->contentPart->getHeaderValue('Content-Type');
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
        $type = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
        $disposition = $part->getHeaderValue('Content-Disposition');
        $isMultipart = preg_match('~multipart/\w+~i', $type);
        if ((!empty($disposition) || !$this->addContentPartFromParsed($part)) && !$isMultipart) {
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
            $type = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
            if ($type === $contentType) {
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
        $type = strtolower($this->contentPart->getHeaderValue('Content-Type', 'text/plain'));
        if ($type === $contentType) {
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
        fwrite($handle, "\r\n--");
        fwrite($handle, $boundary);
        if ($isEnd) {
            fwrite($handle, "--\r\n");
        }
        fwrite($handle, "\r\n");
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
            if ($boundaryParent !== null) {
                $this->writeBoundary($handle, $boundary, true);
            }
            $boundaryParent = $parent;
            $boundary = $boundaryParent->getHeaderParameter('Content-Type', 'boundary');
        }
        if ($boundaryParent !== null) {
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
        } else {
            $part->writeContentTo($handle);
        }
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
        $type = $part->getHeaderValue('Content-Type');
        $disposition = $part->getHeaderValue('Content-Disposition');
        if (empty($disposition) && $this->contentPart != $part && ($type === 'text/html' || $type === 'text/plain')) {
            return $this->contentPart;
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
        $this->writePartsTo($handle, new ArrayIterator($this->parts), $this);
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
