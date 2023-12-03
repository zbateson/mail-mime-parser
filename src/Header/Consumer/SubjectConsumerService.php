<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;
use ZBateson\MailMimeParser\Header\IHeaderPart;
use Iterator;

/**
 * Extends AbstractGenericConsumerService to use a MimeLiteralPartFactory, and
 * to preserve all whitespace and escape sequences as-is (unlike other headers
 * subject headers don't have escape chars such as '\\' for a backslash).
 *
 * SubjectConsumerService doesn't define any sub-consumers.
 *
 * @author Zaahid Bateson
 */
class SubjectConsumerService extends AbstractGenericConsumerService
{
    public function __construct(MimeLiteralPartFactory $partFactory)
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
            return $this->partFactory->newLiteralPart($token);
        } elseif (\preg_match('/^\s+$/', $token)) {
            if (\preg_match('/^[\r\n]/', $token)) {
                return $this->partFactory->newToken(' ');
            }
            return $this->partFactory->newToken($token);
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

    /**
     * Overridden to combine all part values into a single string and return it
     * as an array with a single element.
     *
     * The returned IHeaderParts are all LiteralParts.
     *
     * @param IHeaderPart[] $parts
     * @return IHeaderPart[]
     */
    protected function processParts(array $parts) : array
    {
        $strValue = '';
        $filtered = $this->filterIgnoredSpaces($parts);
        foreach ($filtered as $part) {
            $strValue .= $part->getValue();
        }
        return [$this->partFactory->newLiteralPart($strValue)];
    }
}
