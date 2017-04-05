<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Writer;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\MimePart;

/**
 * Writes out a message in a mail-compliant format.
 * 
 * Provides a way of writing out a ZBateson\MailMimeParser\Message object to a
 * resource handle.
 *
 * @author Zaahid Bateson
 */
class MessageWriter extends MimePartWriter
{
    /**
     * Writes out a mime boundary to the passed $handle optionally writing out a
     * number of empty lines before it.
     * 
     * @param resource $handle
     * @param string $boundary
     * @param int $numLinesBefore
     * @param bool $isEnd
     */
    protected function writeBoundary($handle, $boundary, $numLinesBefore, $isEnd)
    {
        if ($numLinesBefore > 0) {
            fwrite($handle, str_repeat("\r\n", $numLinesBefore));
        }
        fwrite($handle, '--');
        fwrite($handle, $boundary);
        if ($isEnd) {
            fwrite($handle, "--\r\n");
        } else {
            fwrite($handle, "\r\n");
        }
    }

    /**
     * Writes out headers and content for the passed MimePart, then loops over
     * its child parts calling recursiveWriteParts on each part.
     * 
     * @param MimePart $part the current part to write out
     * @param resource $handle the handle to write out to
     * @return bool true if the part had children (and ended with writing a
     *      boundary)
     */
    protected function recursiveWriteParts(MimePart $part, $handle)
    {
        $this->writePartHeadersTo($part, $handle);
        $this->writePartContentTo($part, $handle);
        $ended = false;
        $boundary = $part->getHeaderParameter('Content-Type', 'boundary');
        foreach ($part->getChildParts() as $i => $child) {
            if ($boundary !== null) {
                $numLines = ($i !== 0 && !$ended) ? 2 : (int) $ended;
                $this->writeBoundary($handle, $boundary, $numLines, false);
            }
            $ended = $this->recursiveWriteParts($child, $handle);
        }
        if ($boundary !== null) {
            $this->writeBoundary($handle, $boundary, ($ended) ? 1 : 2, true);
            return true;
        }
        return false;
    }

    /**
     * Saves the message as a MIME message to the passed resource handle.
     * 
     * @param Message $message
     * @param resource $handle
     */
    public function writeMessageTo(Message $message, $handle)
    {
        if ($message->isMime()) {
            $this->recursiveWriteParts($message, $handle);
        } else {
            $this->writePartHeadersTo($message, $handle);
            $this->writePartContentTo($message, $handle);
            foreach ($message->getChildParts() as $i => $child) {
                fwrite($handle, "\r\n\r\n");
                $this->writePartContentTo($child, $handle);
            }
        }
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
        $messagePart = $message->getChild(0);
        if (!$message->isMime() || $messagePart === null) {
            return null;
        }
        $handle = fopen('php://temp', 'r+');
        $ended = $this->recursiveWriteParts($messagePart, $handle);
        rewind($handle);
        $str = stream_get_contents($handle);
        fclose($handle);
        if (!$ended) {
            $str .= "\r\n";
        }
        return $str;
    }
}
