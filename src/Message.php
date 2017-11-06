<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\Part\MimePart;
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
            PartFilter::fromInlineContentType('text/plain')
        );
    }
    
    /**
     * Returns the number of text/plain parts in this message.
     * 
     * @return int
     */
    public function getTextPartCount()
    {
        return $this->getPartCount(PartFilter::fromInlineContentType('text/plain'));
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
            PartFilter::fromInlineContentType('text/html')
        );
    }
    
    /**
     * Returns the number of text/html parts in this message.
     * 
     * @return int
     */
    public function getHtmlPartCount()
    {
        return $this->getPartCount(PartFilter::fromInlineContentType('text/html'));
    }
    
    /**
     * Returns the content MimePart, which could be a text/plain part,
     * text/html part, multipart/alternative part, or null if none is set.
     * 
     * This function is deprecated in favour of getTextPart/getHtmlPart and 
     * getPartByMimeType.
     * 
     * @deprecated since version 0.4.2
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function getContentPart()
    {
        $alternative = $this->getPartByMimeType('multipart/alternative');
        if ($alternative !== null) {
            return $alternative;
        }
        $text = $this->getTextPart();
        return ($text !== null) ? $text : $this->getHtmlPart();
    }
    
    /**
     * Returns a string containing the original message's signed part, useful
     * for verifying the email.
     * 
     * If the signed part of the message ends in a final empty line, the line is
     * removed as it's considered part of the signature's mime boundary.  From
     * RFC-3156:
     * 
     * Note: The accepted OpenPGP convention is for signed data to end
     * with a <CR><LF> sequence.  Note that the <CR><LF> sequence
     * immediately preceding a MIME boundary delimiter line is considered
     * to be part of the delimiter in [3], 5.1.  Thus, it is not part of
     * the signed data preceding the delimiter line.  An implementation
     * which elects to adhere to the OpenPGP convention has to make sure
     * it inserts a <CR><LF> pair on the last line of the data to be
     * signed and transmitted (signed message and transmitted message
     * MUST be identical).
     * 
     * The additional line should be inserted by the signer -- for verification
     * purposes if it's missing, it would seem the content part would've been
     * signed without a last <CR><LF>.
     * 
     * @return string or null if the message doesn't have any children, or the
     *      child returns null for getOriginalStreamHandle
     */
    public function getOriginalMessageStringForSignatureVerification()
    {
        $child = $this->getChild(0);
        if ($child !== null && $child->getOriginalStreamHandle() !== null) {
            $normalized = preg_replace(
                '/\r\n|\r|\n/',
                "\r\n",
                stream_get_contents($child->getOriginalStreamHandle())
            );
            $len = strlen($normalized);
            if ($len > 0 && strrpos($normalized, "\r\n") == $len - 2) {
                return substr($normalized, 0, -2);
            }
            return $normalized;
        }
        return null;
    }

    /**
     * Returns the attachment part at the given 0-based index, or null if none
     * is set.
     * 
     * @param int $index
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
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
     * Attachments are any non-multipart, non-signature and non inline text or
     * html part (a text or html part with a Content-Disposition set to 
     * 'attachment' is considered an attachment).
     * 
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart[]
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
     * Returns a resource handle where the 'inline' text/plain content at the
     * passed $index can be read or null if unavailable.
     * 
     * @param int $index
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
     * Returns the content of the inline text/plain part at the given index.
     * 
     * Reads the entire stream content into a string and returns it.  Returns
     * null if the message doesn't have an inline text part.
     * 
     * @param int $index
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
     * Returns a resource handle where the 'inline' text/html content at the
     * passed $index can be read or null if unavailable.
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
     * Returns the content of the inline text/html part at the given index.
     * 
     * Reads the entire stream content into a string and returns it.  Returns
     * null if the message doesn't have an inline html part.
     * 
     * @param int $index
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
        stream_copy_to_stream($this->handle, $handle);
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
