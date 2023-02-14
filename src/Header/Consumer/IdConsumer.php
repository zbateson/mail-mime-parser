<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Consumer;

use \ZBateson\MailMimeParser\Header\Part\CommentPart;

/**
 * Parses a single ID from an ID header.  Begins consuming on a '<' char, and
 * ends on a '>' char.
 *
 * @author Zaahid Bateson
 */
class IdConsumer extends GenericConsumer
{
    /**
     * Overridden to return patterns matching the beginning part of an ID ('<'
     * and '>' chars).
     *
     * @return string[] the patterns
     */
    public function getTokenSeparators() : array
    {
        return ['\s+', '<', '>'];
    }

    /**
     * Returns true for '>'.
     */
    protected function isEndToken(string $token) : bool
    {
        return ($token === '>');
    }

    /**
     * Returns true for '<'.
     */
    protected function isStartToken(string $token) : bool
    {
        return ($token === '<');
    }

    /**
     * Returns null for whitespace, and LiteralPart for anything else.
     *
     * @param string $token the token
     * @param bool $isLiteral set to true if the token represents a literal -
     *        e.g. an escaped token
     * @return \ZBateson\MailMimeParser\Header\IHeaderPart|null the constructed
     *         header part or null if the token should be ignored
     */
    protected function getPartForToken(string $token, bool $isLiteral)
    {
        if (\preg_match('/^\s+$/', $token)) {
            return null;
        }
        return $this->partFactory->newLiteralPart($token);
    }

    /**
     * Overridden to combine non-comment parts into a single part and return
     * any comment parts after.
     *
     * @param \ZBateson\MailMimeParser\Header\IHeaderPart[] $parts
     * @return \ZBateson\MailMimeParser\Header\IHeaderPart[]
     */
    protected function processParts(array $parts) : array
    {
        $id = \array_reduce(\array_filter($parts), function ($c, $p) {
            return $c . $p->getValue();
        }, '');
        return array_merge([$this->partFactory->newLiteralPart($id)], \array_values(\array_filter($parts, function ($p) {
            return ($p instanceof CommentPart);
        })));
    }
}
