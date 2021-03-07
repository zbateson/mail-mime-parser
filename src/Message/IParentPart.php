<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\PartFilter;

/**
 * An IMessagePart that contains children.
 *
 * @author Zaahid Bateson
 */
interface IParentPart extends IMessagePart
{
    public function getPartChildrenContainer();

    /**
     * Returns the part at the given 0-based index, or null if none is set.
     *
     * Note that the first part returned is the current part itself.  This is
     * often desirable for queries with a PartFilter, e.g. looking for an
     * IMessagePart with a specific Content-Type that may be satisfied by the
     * current part.
     *
     * @param int $index
     * @param PartFilter $filter
     * @return IMessagePart
     */
    public function getPart($index, PartFilter $filter = null);

    /**
     * Returns the current part, all child parts, and child parts of all
     * children optionally filtering them with the provided PartFilter.
     *
     * The first part returned is always the current IMimePart.  This is often
     * desirable as it may be a valid MimePart for the provided PartFilter.
     *
     * @param PartFilter $filter an optional filter
     * @return IMessagePart[]
     */
    public function getAllParts(PartFilter $filter = null);

    /**
     * Returns the total number of parts in this and all children.
     *
     * Note that the current part is considered, so the minimum getPartCount is
     * 1 without a filter.
     *
     * @param PartFilter $filter
     * @return int
     */
    public function getPartCount(PartFilter $filter = null);

    /**
     * Returns the direct child at the given 0-based index, or null if none is
     * set.
     *
     * @param int $index
     * @param PartFilter $filter
     * @return IMessagePart
     */
    public function getChild($index, PartFilter $filter = null);

    /**
     * Returns all direct child parts.
     *
     * If a PartFilter is provided, the PartFilter is applied before returning.
     *
     * @param PartFilter $filter
     * @return IMessagePart[]
     */
    public function getChildParts(PartFilter $filter = null);

    /**
     * Returns the number of direct children under this part.
     *
     * @param PartFilter $filter
     * @return int
     */
    public function getChildCount(PartFilter $filter = null);

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
     * @param \ZBateson\MailMimeParser\Message\PartFilter $filter
     */
    public function removeAllParts(PartFilter $filter = null);
}
