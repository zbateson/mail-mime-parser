<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Message\IMimePart;
use InvalidArgumentException;

/**
 * Provides a way to define a filter of IMessagePart for use in various calls to
 * add/remove IMessagePart.
 * 
 * A PartFilter is defined as a set of properties in the class, set to either be
 * 'included' or 'excluded'.  The filter is simplistic in that a property
 * defined as included must be set on a part for it to be passed, and an
 * excluded filter must not be set for the part to be passed.  There is no
 * provision for creating logical conditions.
 * 
 * The only property set by default is $signedpart, which defaults to
 * FILTER_EXCLUDE.
 * 
 * A PartFilter can be instantiated with an array of keys matching class
 * properties, and values to set them for convenience.
 * 
 * ```php
 * $inlineParts = $message->getAllParts(new PartFilter([
 *     'multipart' => PartFilter::FILTER_INCLUDE,
 *     'headers' => [ 
 *         FILTER_EXCLUDE => [
 *             'Content-Disposition': 'attachment'
 *         ]
 *     ]
 * ]));
 * 
 * $inlineTextPart = $message->getAllParts(PartFilter::fromInlineContentType('text/plain'));
 * ```
 *
 * @author Zaahid Bateson
 */
class PartFilter
{
    public static function fromAttachmentFilter()
    {
        return function (IMessagePart $part) {
            $type = strtolower($part->getContentType());
            if (in_array($type, [ 'text/plain', 'text/html' ]) && strcasecmp($part->getContentDisposition(), 'inline') === 0) {
                return false;
            }
            return !(($part instanceof IMimePart) && ($part->isMultiPart() || $part->isSignaturePart()));
        };
    }

    public static function fromHeaderValue($name, $value, $excludeSignedParts = true)
    {
        return function(IMessagePart $part) use ($name, $value, $excludeSignedParts) {
            if ($part instanceof IMimePart) {
                if ($excludeSignedParts && $part->isSignaturePart()) {
                    return false;
                }
                return strcasecmp($part->getHeaderValue($name), $value) === 0;
            }
            return false;
        };
    }

    /**
     * Convenience method to filter for a specific mime type.
     * 
     * @param string $mimeType
     * @return PartFilter
     */
    public static function fromContentType($mimeType)
    {
        return function(IMessagePart $part) use ($mimeType) {
            return strcasecmp($part->getContentType(), $mimeType) === 0;
        };
    }

    /**
     * Convenience method to look for parts of a specific mime-type, and that
     * do not specifically have a Content-Disposition equal to 'attachment'.
     * 
     * @param string $mimeType
     * @return PartFilter
     */
    public static function fromInlineContentType($mimeType)
    {
        return function(IMessagePart $part) use ($mimeType) {
            return strcasecmp($part->getContentType(), $mimeType) === 0
                && strcasecmp($part->getContentDisposition(), 'attachment') !== 0;
        };
    }

    /**
     * Convenience method to search for parts with a specific
     * Content-Disposition, optionally including multipart parts.
     * 
     * @param string $disposition
     * @param int $multipart
     * @return PartFilter
     */
    public static function fromDisposition($disposition, $includeMultipart = false, $excludeSignedParts = true)
    {
        return function(IMessagePart $part) use ($disposition, $includeMultipart, $excludeSignedParts) {
            if (($part instanceof IMimePart) && (($excludeSignedParts && $part->isSignaturePart()) || (!$includeMultipart && $part->isMultiPart()))) {
                return false;
            }
            return strcasecmp($part->getContentDisposition(), $disposition) === 0;
        };
    }
}
