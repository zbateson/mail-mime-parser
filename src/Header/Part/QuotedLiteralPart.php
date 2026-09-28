<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

/**
 * A quoted literal header string part.  The value of the part is stripped of CR
 * and LF characters, and whitespace between two adjacent MimeTokens is removed.
 *
 * @author Zaahid Bateson
 */
class QuotedLiteralPart extends ContainerPart
{
    /**
     * Strips spaces found between two adjacent MimeToken parts.
     * Other whitespace is returned as-is.
     *
     * @param HeaderPart[] $parts
     * @return HeaderPart[]
     */
    protected function filterIgnoredSpaces(array $parts) : array
    {
        $filtered = [];
        $count = \count($parts);
        for ($key = 0; $key < $count; ++$key) {
            $cur = $parts[$key];
            $last = ($filtered !== []) ? \end($filtered) : null;
            $next = ($key + 1 < $count) ? $parts[$key + 1] : null;
            if ($last !== null && $next !== null && $cur->isSpace && (
                $last->canIgnoreSpacesAfter
                && $next->canIgnoreSpacesBefore
                && $last instanceof MimeToken
                && $next instanceof MimeToken
            )) {
                continue;
            }
            $filtered[] = $cur;
        }
        return $filtered;
    }
}
