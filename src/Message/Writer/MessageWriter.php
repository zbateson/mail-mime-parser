<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Writer;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Message\NonMimePart;
use ArrayIterator;
use Iterator;

/**
 * Writes out a message in a mail-compliant format.
 * 
 * Provides a way of writing out a ZBateson\MailMimeParser\Message object to a
 * resource handle.
 * 
 * The saved message is not guaranteed to be the same as the parsed message.
 * Namely, for mime messages anything that is not text/html or text/plain
 * will be moved into parts under the main 'message' as attachments, other
 * alternative parts are dropped, and multipart/related parts are ignored
 * (their contents are either moved under a multipart/alternative part or as
 * attachments below the main multipart/mixed message).
 *
 * @author Zaahid Bateson
 */
class MessageWriter extends MimePartWriter
{    
    /**
     * Writes out a mime boundary to the passed $handle.
     * 
     * @param resource $handle
     * @param string $boundary
     * @param bool &$insertNewLineBeforeBoundary
     * @param bool $isEnd
     */
    private function writeBoundary($handle, $boundary, &$insertNewLineBeforeBoundary, $isEnd = false)
    {
        if ($insertNewLineBeforeBoundary) {
            fwrite($handle, "\r\n");
        }
        fwrite($handle, '--');
        fwrite($handle, $boundary);
        if ($isEnd) {
            fwrite($handle, "--\r\n");
        } else {
            fwrite($handle, "\r\n");
        }
        $insertNewLineBeforeBoundary = $isEnd;
    }
    
    /**
     * Writes out any necessary boundaries for the given $part if required based
     * on its $parent and $boundaryParent.
     * 
     * Also writes out end boundaries for the previous part if applicable.
     * 
     * @param resource $handle
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     * @param \ZBateson\MailMimeParser\Message\MimePart $parent
     * @param \ZBateson\MailMimeParser\Message\MimePart $boundaryParent
     * @param string $boundary
     * @param bool &$insertNewLineBeforeBoundary
     */
    private function writePartBoundaries(
        $handle,
        MimePart $part,
        MimePart $parent,
        MimePart &$boundaryParent,
        $boundary,
        &$insertNewLineBeforeBoundary
    ) {
        if ($boundaryParent !== $parent && $boundaryParent !== $part) {
            if ($boundaryParent !== null && $parent->getParent() !== $boundaryParent) {
                $this->writeBoundary($handle, $boundary, $insertNewLineBeforeBoundary, true);
            }
            $boundaryParent = $parent;
            $boundary = $boundaryParent->getHeaderParameter('Content-Type', 'boundary');
        }
        if ($boundaryParent !== null && $boundaryParent !== $part) {
            $this->writeBoundary($handle, $boundary, $insertNewLineBeforeBoundary);
        }
    }
    
    /**
     * Writes out the passed mime part, writing out any necessary mime
     * boundaries.
     * 
     * @param resource $handle
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     * @param \ZBateson\MailMimeParser\Message\MimePart $parent
     * @param \ZBateson\MailMimeParser\Message\MimePart $boundaryParent
     * @param bool &$insertNewLineBeforeBoundary
     */
    private function writeMessagePartTo(
        $handle,
        MimePart $part,
        MimePart $parent,
        MimePart &$boundaryParent,
        &$insertNewLineBeforeBoundary
    ) {
        $boundary = $boundaryParent->getHeaderParameter('Content-Type', 'boundary');
        if ($boundary !== null) {
            $this->writePartBoundaries($handle, $part, $parent, $boundaryParent, $boundary, $insertNewLineBeforeBoundary);
            if ($part !== $this) {
                $this->writePartTo($part, $handle);
            } else {
                $this->writePartContentTo($part, $handle);
            }
        } elseif ($part instanceof NonMimePart) {
            fwrite($handle, "\r\n\r\n");
            $this->writePartContentTo($part, $handle);
        } else {
            $this->writePartContentTo($part, $handle);
        }
        $insertNewLineBeforeBoundary = $part->hasContent();
    }
    
    /**
     * Returns the written parent part of the passed $part.
     * 
     * @param \ZBateson\MailMimeParser\Message $message
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     * @param \ZBateson\MailMimeParser\Message\MimePart $contentPart
     * @param \ZBateson\MailMimeParser\Message\MimePart $signedSignaturePart
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    private function getWriteParentForPart(
        Message $message,
        MimePart $part,
        MimePart $contentPart = null,
        MimePart $signedSignaturePart = null
    ) {
        $type = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
        $disposition = $part->getHeaderValue('Content-Disposition');
        if ($disposition === null && $contentPart !== $part && ($type === 'text/html' || $type === 'text/plain')) {
            return $contentPart;
        } elseif ($signedSignaturePart !== null) {
            return $part->getParent();
        }
        return $message;
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
     * @param Message $message the current message object
     * @param resource $handle the handle to write out to
     * @param Iterator $partsIter an Iterator for parts to save
     * @param \ZBateson\MailMimeParser\Message\MimePart $curParent the current
     *        parent
     */
    protected function writeMessagePartsTo(Message $message, $handle, Iterator $partsIter, MimePart $curParent)
    {
        $insertNewLineBeforeBoundary = false;
        $boundary = $curParent->getHeaderParameter('Content-Type', 'boundary');
        while ($partsIter->valid()) {
            $part = $partsIter->current();
            $parent = $this->getWriteParentForPart($message, $part, $message->getContentPart(), $message->getSignaturePart());
            $this->writeMessagePartTo($handle, $part, $parent, $curParent, $insertNewLineBeforeBoundary);
            $partsIter->next();
        }
        if ($boundary !== null) {
            $this->writeBoundary($handle, $boundary, $insertNewLineBeforeBoundary, true);
        }
    }
    
    /**
     * Returns an array of non-signature MimeParts for the passed $message.
     * 
     * @param Message $message
     * @return MimePart[]
     */
    private function getNonSignaturePartsFromMessage(Message $message)
    {
        $contentPart = $message->getContentPart();
        $parts = [];
        if ($contentPart !== null) {
            $parts[] = $contentPart;
            if ($contentPart->isMultiPart()) {
                $parts = array_merge($parts, $contentPart->getAllParts());
            }
        }
        if ($message->getAttachmentCount() !== 0) {
            $parts = array_merge($parts, $message->getAllAttachmentParts());
        }
        return $parts;
    }
    
    /**
     * Saves the message as a MIME message to the passed resource handle.
     * 
     * @param Message $message
     * @param resource $handle
     */
    public function writeMessageTo(Message $message, $handle)
    {
        $this->writePartHeadersTo($message, $handle);
        $parts = array_merge(
            [ $message->getSignedMixedPart() ],
            $this->getNonSignaturePartsFromMessage($message),
            [ $message->getSignaturePart() ]
        );
        $this->writeMessagePartsTo(
            $message,
            $handle,
            new ArrayIterator(array_filter($parts)),
            $message
        );
    }
    
    /**
     * Writes out the content of the message into a string and returns it.
     * 
     * @return string
     */
    private function getSignableBodyFromParts(Message $message, array $parts)
    {
        $handle = fopen('php://temp', 'r+');
        $firstPart = array_shift($parts);
        $this->writePartHeadersTo($firstPart, $handle);
        $this->writePartContentTo($firstPart, $handle);
        if (!empty($parts)) {
            $this->writeMessagePartsTo(
                $message,
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
     * @param Message $message
     * @return string
     */
    public function getSignableBody(Message $message)
    {
        $parts = array_merge(
            [ $message->getSignedMixedPart() ],
            $this->getNonSignaturePartsFromMessage($message)
        );
        return $this->getSignableBodyFromParts($message, array_filter($parts));
    }
}
