<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Message\Part\MimePart;
use ZBateson\MailMimeParser\Message\PartFilter;
use GuzzleHttp\Psr7;

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
     * Overridden to close the main message StreamInterface used by this
     * message.
     */
    public function __destruct()
    {
        if ($this->stream !== null) {
            $this->stream->close();
        }
    }

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
     * Returns the text/plain part at the given index (or null if not found.)
     * 
     * @param int $index
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function getTextPart($index = 0)
    {
        return $this->getPart(
            $index,
            $this->partFilterFactory->newFilterFromInlineContentType('text/plain')
        );
    }
    
    /**
     * Returns the number of text/plain parts in this message.
     * 
     * @return int
     */
    public function getTextPartCount()
    {
        return $this->getPartCount(
            $this->partFilterFactory->newFilterFromInlineContentType('text/plain')
        );
    }
    
    /**
     * Returns the text/html part at the given index (or null if not found.)
     * 
     * @param $index
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function getHtmlPart($index = 0)
    {
        return $this->getPart(
            $index,
            $this->partFilterFactory->newFilterFromInlineContentType('text/html')
        );
    }
    
    /**
     * Returns the number of text/html parts in this message.
     * 
     * @return int
     */
    public function getHtmlPartCount()
    {
        return $this->getPartCount(
            $this->partFilterFactory->newFilterFromInlineContentType('text/html')
        );
    }

    /**
     * Returns a string containing the entire body of a signed message for
     * verification.
     * 
     * @return string or null if the message doesn't have any children, or the
     *      child returns null for getHandle
     */
    public function getMessageStringForSignatureVerification()
    {
        $child = $this->getChild(0);
        if ($child !== null && $child->getHandle() !== null) {
            $normalized = preg_replace(
                '/\r\n|\r|\n/',
                "\r\n",
                stream_get_contents($child->getHandle())
            );
            return $normalized;
        }
        return null;
    }
    
    /**
     * Returns the signature part of a multipart/signed message or null.
     *
     * The signature part is determined to always be the 2nd child of a
     * multipart/signed message, the first being the 'body'.
     *
     * Using the 'protocol' parameter of the Content-Type header is unreliable
     * in some instances (for instance a difference of x-pgp-signature versus
     * pgp-signature).
     * 
     * @return MimePart
     */
    public function getSignaturePart()
    {
        $contentType = $this->getHeaderValue('Content-Type', 'text/plain');
        if (strcasecmp($contentType, 'multipart/signed') === 0) {
            return $this->getChild(1);
        } else {
            return null;
        }
    }

    /**
     * Returns the attachment part at the given 0-based index, or null if none
     * is set.
     * 
     * @param int $index
     * @return MessagePart
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
     * "Attachments" are any non-multipart, non-signature and any text or html
     * html part witha Content-Disposition set to  'attachment'.
     * 
     * @return MessagePart[]
     */
    public function getAllAttachmentParts()
    {
        $parts = $this->getAllParts(
            $this->partFilterFactory->newFilterFromArray([
                'multipart' => PartFilter::FILTER_EXCLUDE
            ])
        );
        return array_values(array_filter(
            $parts,
            function ($part) {
                return !(
                    $part->isTextPart()
                    && $part->getContentDisposition() === 'inline'
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
     * Returns a resource handle where the 'inline' text/plain content at the
     * passed $index can be read or null if unavailable.
     * 
     * @param int $index
     * @param string $charset
     * @return resource
     */
    public function getTextStream($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $textPart = $this->getTextPart($index);
        if ($textPart !== null) {
            return $textPart->getContentResourceHandle($charset);
        }
        return null;
    }

    /**
     * Returns the content of the inline text/plain part at the given index.
     * 
     * Reads the entire stream content into a string and returns it.  Returns
     * null if the message doesn't have an inline text part.
     * 
     * @param int $index
     * @param string $charset
     * @return string
     */
    public function getTextContent($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $part = $this->getTextPart($index);
        if ($part !== null) {
            return $part->getContent($charset);
        }
        return null;
    }

    /**
     * Returns a resource handle where the 'inline' text/html content at the
     * passed $index can be read or null if unavailable.
     * 
     * @param int $index
     * @param string $charset
     * @return resource
     */
    public function getHtmlStream($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $htmlPart = $this->getHtmlPart($index);
        if ($htmlPart !== null) {
            return $htmlPart->getContentResourceHandle($charset);
        }
        return null;
    }

    /**
     * Returns the content of the inline text/html part at the given index.
     * 
     * Reads the entire stream content into a string and returns it.  Returns
     * null if the message doesn't have an inline html part.
     * 
     * @param int $index
     * @param string $charset
     * @return string
     */
    public function getHtmlContent($index = 0, $charset = MailMimeParser::DEFAULT_CHARSET)
    {
        $part = $this->getHtmlPart($index);
        if ($part !== null) {
            return $part->getContent($charset);
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
        if ($this->stream !== null) {
            $dest = Psr7\stream_for($handle);
            $this->stream->rewind();
            Psr7\copy_to_stream($this->stream, $dest);
            // don't close when out of scope
            $dest->detach();
        }
    }

    /**
     * Shortcut to call Message::save with a php://temp stream and return the
     * written email message as a string.
     * 
     * @return string
     */
    public function __toString()
    {
        if ($this->stream !== null) {
            $this->stream->rewind();
            return $this->stream->getContents();
        }
        return '';
    }
}
