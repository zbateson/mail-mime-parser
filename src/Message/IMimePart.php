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
 * An IMimePart object may have any number of child parts, or may be a child
 * itself with its own parent or parents.
 *
 * @author Zaahid Bateson
 */
interface IMimePart extends IMessagePart
{
    /**
     * Returns true if this part's mime type is multipart/*
     *
     * @return bool
     */
    public function isMultiPart();

    /**
     * Returns true if this part is the second child of a multipart/signed
     * message.
     *
     * @return bool
     */
    public function isSignaturePart();

    /**
     * Convenience method to find a part by its Content-ID header.
     *
     * @param string $contentId
     * @return IMessagePart
     */
    public function getPartByContentId($contentId);

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
     * @param callable $fnFilter
     * @return IMessagePart
     */
    public function getPart($index, $fnFilter = null);

    /**
     * Returns the current part, all child parts, and child parts of all
     * children optionally filtering them with the provided PartFilter.
     *
     * The first part returned is always the current IMimePart.  This is often
     * desirable as it may be a valid MimePart for the provided PartFilter.
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
     * @param callable $fnFilter
     * @return int
     */
    public function getPartCount($fnFilter = null);

    /**
     * Returns the direct child at the given 0-based index, or null if none is
     * set.
     *
     * @param int $index
     * @param callable $fnFilter
     * @return IMessagePart
     */
    public function getChild($index, $fnFilter = null);

    /**
     * Returns all direct child parts.
     *
     * If a is provided, the is applied before returning.
     *
     * @param callable $fnFilter
     * @return IMessagePart[]
     */
    public function getChildParts($fnFilter = null);

    /**
     * Returns the number of direct children under this part.
     *
     * @param callable $fnFilter
     * @return int
     */
    public function getChildCount($fnFilter = null);

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
     * @param \ZBateson\MailMimeParser\Message\$fnFilter
     */
    public function removeAllParts($fnFilter = null);

    /**
     * Returns the AbstractHeader object for the header with the given $name.
     * If the optional $offset is passed, and multiple headers exist with the
     * same name, the one at the passed offset is returned.
     *
     * Note that mime headers aren't case sensitive.
     *
     * @param string $name
     * @param int $offset
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader
     *         |\ZBateson\MailMimeParser\Header\AddressHeader
     *         |\ZBateson\MailMimeParser\Header\DateHeader
     *         |\ZBateson\MailMimeParser\Header\GenericHeader
     *         |\ZBateson\MailMimeParser\Header\IdHeader
     *         |\ZBateson\MailMimeParser\Header\ParameterHeader
     *         |\ZBateson\MailMimeParser\Header\ReceivedHeader
     *         |\ZBateson\MailMimeParser\Header\SubjectHeader
     */
    public function getHeader($name, $offset = 0);

    /**
     * Returns an array of headers in this part.
     *
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader[]
     */
    public function getAllHeaders();

    /**
     * Returns an array of headers that match the passed name.
     *
     * @param string $name
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader[]
     */
    public function getAllHeadersByName($name);

    /**
     * Returns an array of all headers for the mime part with the first element
     * holding the name, and the second its value.
     *
     * @return string[][]
     */
    public function getRawHeaders();

    /**
     * Returns an iterator to the headers in this collection.  Each returned
     * element is an array with its first element set to the header's name, and
     * the second to its raw value:
     *
     * [ 'Header-Name', 'Header Value' ]
     *
     * @return \Iterator
     */
    public function getRawHeaderIterator();

    /**
     * Returns the string value for the header with the given $name.
     *
     * Note that mime headers aren't case sensitive.
     *
     * @param string $name
     * @param string $defaultValue
     * @return string
     */
    public function getHeaderValue($name, $defaultValue = null);

    /**
     * Returns a parameter of the header $header, given the parameter named
     * $param.
     *
     * Only headers of type
     * \ZBateson\MailMimeParser\Header\ParameterHeader have parameters.
     * Content-Type and Content-Disposition are examples of headers with
     * parameters. "Charset" is a common parameter of Content-Type.
     *
     * @param string $header
     * @param string $param
     * @param string $defaultValue
     * @return string
     */
    public function getHeaderParameter($header, $param, $defaultValue = null);

    /**
     * Adds a header with the given $name and $value.  An optional $offset may
     * be passed, which will overwrite a header if one exists with the given
     * name and offset. Otherwise a new header is added.  The passed $offset may
     * be ignored in that case if it doesn't represent the next insert position
     * for the header with the passed name... instead it would be 'pushed' on at
     * the next position.
     *
     * ```php
     * $part = $myParentHeaderPart;
     * $part->setRawHeader('New-Header', 'value');
     * echo $part->getHeaderValue('New-Header');        // 'value'
     *
     * $part->setRawHeader('New-Header', 'second', 4);
     * echo is_null($part->getHeader('New-Header', 4)); // '1' (true)
     * echo $part->getHeader('New-Header', 1)
     *      ->getValue();                               // 'second'
     * ```
     *
     * A new \ZBateson\MailMimeParser\Header\AbstractHeader object is created
     * from the passed value.  No processing on the passed string is performed,
     * and so the passed name and value must be formatted correctly according to
     * related RFCs.  In particular, be careful to encode non-ascii data, to
     * keep lines under 998 characters in length, and to follow any special
     * formatting required for the type of header.
     *
     * @param string $name
     * @param string $value
     * @param int $offset
     */
    public function setRawHeader($name, $value, $offset = 0);

    /**
     * Adds a header with the given $name and $value.
     *
     * Note: If a header with the passed name already exists, a new header is
     * created with the same name.  This should only be used when that is
     * intentional - in most cases setRawHeader should be called.
     *
     * Creates a new \ZBateson\MailMimeParser\Header\AbstractHeader object and
     * registers it as a header.
     *
     * @param string $name
     * @param string $value
     */
    public function addRawHeader($name, $value);

    /**
     * Removes all headers from this part with the passed name.
     *
     * @param string $name
     */
    public function removeHeader($name);

    /**
     * Removes a single header with the passed name (in cases where more than
     * one may exist, and others should be preserved).
     *
     * @param string $name
     */
    public function removeSingleHeader($name, $offset = 0);
}
