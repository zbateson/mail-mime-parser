<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

/**
 * Represents a single part of a multi-part mime message.
 *
 * An IMultiPart object may have any number of child parts, or may be a child
 * itself with its own parent or parents.
 *
 * @author Zaahid Bateson
 */
interface IMultiPart extends IMessagePart
{
    /**
     * Returns true if this part's mime type is multipart/*
     *
     * @return bool
     */
    public function isMultiPart();

    /**
     * Returns the part at the given 0-based index, or null if none is set.
     *
     * Note that the first part returned is the current part itself.  This is
     * often desirable for queries with a passed filter, e.g. looking for an
     * IMessagePart with a specific Content-Type that may be satisfied by the
     * current part.
     *
     * The passed callable must accept an {@see IMessagePart} as an argument,
     * and return true if it should be accepted, or false to filter the part
     * out.  Some default filters are provided by static functions returning
     * callables in {@see PartFilter}.
     *
     * @param int $index
     * @param callable $fnFilter
     * @return IMessagePart
     */
    public function getPart($index, $fnFilter = null);

    /**
     * Returns the current part, all child parts, and child parts of all
     * children optionally filtering them with the provided PartFilter.
     *
     * Note that the first part returned is the current part itself.  This is
     * often desirable for queries with a passed filter, e.g. looking for an
     * IMessagePart with a specific Content-Type that may be satisfied by the
     * current part.
     *
     * The passed callable must accept an {@see IMessagePart} as an argument,
     * and return true if it should be accepted, or false to filter the part
     * out.  Some default filters are provided by static functions returning
     * callables in {@see PartFilter}.
     *
     * @param callable $fnFilter an optional filter
     * @return IMessagePart[]
     */
    public function getAllParts($fnFilter = null);

    /**
     * Returns the total number of parts in this and all children.
     *
     * Note that the current part is considered, so the minimum getPartCount is
     * 1 without a filter.
     *
     * The passed callable must accept an {@see IMessagePart} as an argument,
     * and return true if it should be accepted, or false to filter the part
     * out.  Some default filters are provided by static functions returning
     * callables in {@see PartFilter}.
     *
     * @param callable $fnFilter
     * @return int
     */
    public function getPartCount($fnFilter = null);

    /**
     * Returns the direct child at the given 0-based index, or null if none is
     * set.
     *
     * The passed callable must accept an {@see IMessagePart} as an argument,
     * and return true if it should be accepted, or false to filter the part
     * out.  Some default filters are provided by static functions returning
     * callables in {@see PartFilter}.
     *
     * @param int $index
     * @param callable $fnFilter
     * @return IMessagePart
     */
    public function getChild($index, $fnFilter = null);

    /**
     * Returns all direct child parts.
     *
     * The passed callable must accept an {@see IMessagePart} as an argument,
     * and return true if it should be accepted, or false to filter the part
     * out.  Some default filters are provided by static functions returning
     * callables in {@see PartFilter}.
     *
     * @param callable $fnFilter
     * @return IMessagePart[]
     */
    public function getChildParts($fnFilter = null);

    /**
     * Returns the number of direct children under this part.
     *
     * The passed callable must accept an {@see IMessagePart} as an argument,
     * and return true if it should be accepted, or false to filter the part
     * out.  Some default filters are provided by static functions returning
     * callables in {@see PartFilter}.
     *
     * @param callable $fnFilter
     * @return int
     */
    public function getChildCount($fnFilter = null);

    /**
     * Returns an iterator for child parts.
     *
     * @return \RecursiveIterator
     */
    public function getChildIterator();

    /**
     * Returns the part associated with the passed mime type, at the passed
     * index, if it exists.
     *
     * @param string $mimeType
     * @param int $index
     * @return IMessagePart|null
     */
    public function getPartByMimeType($mimeType, $index = 0);

    /**
     * Returns an array of all parts associated with the passed mime type if any
     * exist or null otherwise.
     *
     * @param string $mimeType
     * @return IMessagePart[] or null
     */
    public function getAllPartsByMimeType($mimeType);

    /**
     * Returns the number of parts matching the passed $mimeType
     *
     * @param string $mimeType
     * @return int
     */
    public function getCountOfPartsByMimeType($mimeType);

    /**
     * Convenience method to find a part by its Content-ID header.
     *
     * @param string $contentId
     * @return IMessagePart
     */
    public function getPartByContentId($contentId);

    /**
     * Registers the passed part as a child of the current part.
     *
     * If the $position parameter is non-null, adds the part at the passed
     * position index.
     *
     * @param IMessagePart $part
     * @param int $position
     */
    public function addChild(IMessagePart $part, $position = null);

    /**
     * Removes the child part from this part and returns its position or
     * null if it wasn't found.
     *
     * Note that if the part is not a direct child of this part, the returned
     * position is its index within its parent (calls removePart on its direct
     * parent).
     *
     * @param IMessagePart $part
     * @return int or null if not found
     */
    public function removePart(IMessagePart $part);

    /**
     * Removes all parts that are matched by the passed PartFilter.
     *
     * Note: the current part will not be removed.  Although the function naming
     * matches getAllParts, which returns the current part, it also doesn't only
     * remove direct children like getChildParts.  Internally this function uses
     * getAllParts but the current part is filtered out if returned.
     *
     * @param \ZBateson\MailMimeParser\Message\$fnFilter
     */
    public function removeAllParts($fnFilter = null);
}
