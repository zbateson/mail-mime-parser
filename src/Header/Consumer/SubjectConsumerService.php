<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Consumer;

use Iterator;
use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory;

/**
 * Extends AbstractGenericConsumerService to use a MimeTokenPartFactory, and
 * to preserve all whitespace and escape sequences as-is (unlike other headers
 * subject headers don't have escape chars such as '\\' for a backslash).
 *
 * SubjectConsumerService doesn't define any sub-consumers.
 *
 * @author Zaahid Bateson
 */
class SubjectConsumerService extends AbstractGenericConsumerService
{
    public function __construct(MimeTokenPartFactory $partFactory)
    {
        parent::__construct($partFactory);
    }

    /**
     * Overridden to preserve whitespace.
     *
     * Whitespace between two words is preserved unless the whitespace begins
     * with a newline (\n or \r\n), in which case the entire string of
     * whitespace is discarded, and a single space ' ' character is used in its
     * place.
     */
    protected function getPartForToken(string $token, bool $isLiteral) : ?IHeaderPart
    {
        if ($isLiteral) {
            return $this->partFactory->newToken($token, true);
        } elseif (\preg_match('/^\s+$/', $token)) {
            return $this->partFactory->newToken(' ');
        }
        return $this->partFactory->newInstance($token);
    }

    /**
     * Returns an array of \ZBateson\MailMimeParser\Header\Part\HeaderPart for
     * the current token on the iterator.
     *
     * Overridden from AbstractConsumerService to remove special filtering for
     * backslash escaping, which also seems to not apply to Subject headers at
     * least in ThunderBird's implementation.
     *
     * @return IHeaderPart[]
     */
    protected function getTokenParts(Iterator $tokens) : array
    {
        return $this->getConsumerTokenParts($tokens);
    }

    /**
     * Overridden to not split out backslash characters and its next character
     * as a special case defined in AbstractConsumerService
     *
     * @return string the regex pattern
     */
    protected function getTokenSplitPattern() : string
    {
        $sChars = \implode('|', $this->getAllTokenSeparators());
        return '~(' . $sChars . ')~';
    }
}
