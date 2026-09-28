<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Message;

/**
 * Collection of static methods that return callables for common IMultiPart
 * child filters.
 *
 * @author Zaahid Bateson
 */
final class PartFilter
{
    private function __construct()
    {
    }

    /**
     * Returns true if any ancestor of the passed part is a multipart part with
     * an 'attachment' disposition.  A disposition on a multipart applies to
     * the multipart as a whole, so everything under it is part of that
     * attachment.
     */
    private static function isWithinAttachedMultipart(IMessagePart $part) : bool
    {
        for ($parent = $part->getParent(); $parent !== null; $parent = $parent->getParent()) {
            if ($parent->isMultiPart() && \strcasecmp($parent->getContentDisposition() ?? '', 'attachment') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Provides an 'attachment' filter used by Message::getAttachmentPart.
     *
     * The method filters out the following types of parts:
     *  - text/plain and text/html parts that do not have an 'attachment'
     *    disposition
     *  - any part that returns true for isMultiPart(), unless it has an
     *    'attachment' disposition itself
     *  - any part under a multipart part with an 'attachment' disposition
     *  - any part that returns true for isSignaturePart()
     */
    public static function fromAttachmentFilter() : callable
    {
        return function(IMessagePart $part) {
            if (self::isWithinAttachedMultipart($part)) {
                return false;
            }
            $type = $part->getContentType();
            $disp = $part->getContentDisposition();
            if (\in_array($type, ['text/plain', 'text/html']) && $disp !== null && \strcasecmp($disp, 'inline') === 0) {
                return false;
            }
            if (!($part instanceof IMimePart)) {
                return true;
            }
            if ($part->isMultiPart()) {
                return ($disp !== null && \strcasecmp($disp, 'attachment') === 0);
            }
            return !$part->isSignaturePart();
        };
    }

    /**
     * Provides a filter that keeps parts that contain a header of $name with a
     * value that matches $value (case insensitive).
     *
     * By default signed parts are excluded. Pass FALSE to the third parameter
     * to include them.
     *
     * @param string $name The header name to look up
     * @param string $value The value to match
     * @param bool $excludeSignedParts Optional signed parts exclusion (defaults
     *        to true).
     */
    public static function fromHeaderValue(string $name, string $value, bool $excludeSignedParts = true) : callable
    {
        return function(IMessagePart $part) use ($name, $value, $excludeSignedParts) {
            if ($part instanceof IMimePart) {
                if ($excludeSignedParts && $part->isSignaturePart()) {
                    return false;
                }
                return (\strcasecmp($part->getHeaderValue($name, ''), $value) === 0);
            }
            return false;
        };
    }

    /**
     * Includes only parts that match the passed $mimeType in the return value
     * of a call to 'getContentType()'.
     *
     * @param string $mimeType Mime type of parts to find.
     */
    public static function fromContentType(string $mimeType) : callable
    {
        return fn(IMessagePart $part) => \strcasecmp($part->getContentType(), $mimeType) === 0;
    }

    /**
     * Returns parts matching $mimeType that do not have a Content-Disposition
     * set to 'attachment', and are not under a multipart part that does.
     *
     * @param string $mimeType Mime type of parts to find.
     */
    public static function fromInlineContentType(string $mimeType) : callable
    {
        return function(IMessagePart $part) use ($mimeType) {
            $disp = $part->getContentDisposition();
            return (\strcasecmp($part->getContentType(), $mimeType) === 0)
                && ($disp === null || \strcasecmp($disp, 'attachment') !== 0)
                && !self::isWithinAttachedMultipart($part);
        };
    }

    /**
     * Finds parts with the passed disposition (matching against
     * IMessagePart::getContentDisposition()), optionally including
     * multipart parts and signed parts.
     *
     * @param string $disposition The disposition to find.
     * @param bool $includeMultipart Optionally include multipart parts by
     *        passing true (defaults to false).
     * @param bool $includeSignedParts Optionally include signed parts (defaults
     *        to false).
     */
    public static function fromDisposition(string $disposition, bool $includeMultipart = false, bool $includeSignedParts = false) : callable
    {
        return function(IMessagePart $part) use ($disposition, $includeMultipart, $includeSignedParts) {
            if (($part instanceof IMimePart) && ((!$includeMultipart && $part->isMultiPart()) || (!$includeSignedParts && $part->isSignaturePart()))) {
                return false;
            }
            $disp = $part->getContentDisposition();
            return ($disp !== null && \strcasecmp($disp, $disposition) === 0);
        };
    }
}
