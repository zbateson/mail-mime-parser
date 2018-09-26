<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header;

/**
 * Represents an In-Reply-To or Reference header, which contain lists of
 * MessageIDs, and provides a getIds function that returns an array of the IDs
 * in the header.
 * 
 * @author Zaahid Bateson
 */
class MultipleIdHeader extends GenericHeader
{
    /**
     * Strips out leading and trailing less than and greater than ('<', '>')
     * chars to return just the ID portions of the header.
     *
     * An empty array may be returned if the header's value is empty.
     *
     * For example, a header value of '<123.456@example.com>
     * <321.654@example.com>' would return the
     * array [ '123.456@example.com', '321.654@example.com' ].
     * 
     * @return string[]
     */
    public function getIds()
    {
        $value = $this->getValue();
        if ($value === null) {
            return [];
        }
        $ret = preg_split('/>\s*</', preg_replace('/^<|>$/', '', $value));
        if ($ret === false) {
            return [];
        }
        return $ret;
    }
}
