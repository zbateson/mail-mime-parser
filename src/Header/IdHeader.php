<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header;

/**
 * Represents a Content-ID or Message-ID header.
 * 
 * @author Zaahid Bateson
 */
class IdHeader extends GenericHeader
{
    /**
     * Strips out leading and trailing less than and greater than ('<', '>')
     * chars to return just the ID portion of the header.  If '<>' chars aren't
     * found, the value is returned as-is.
     *
     * For example, a header value of <123.456@example.com> would return the
     * string '123.456@example.com'.
     * 
     * @return string
     */
    public function getId()
    {
        return preg_replace('/^<|>$/', '', $this->getValue());
    }
}
