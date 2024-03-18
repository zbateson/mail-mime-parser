<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\CommentPart;
use ZBateson\MailMimeParser\Header\Part\HeaderPart;
use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * A minimal implementation of AbstractConsumerService splitting tokens by
 * whitespace.
 *
 * Although the class doesn't have any abstract methods, it's defined as
 * abstract because it doesn't define specific sub-consumers as constructor
 * dependencies, and so is defined as abstract to avoid its direct use (use
 * the concrete GenericConsumerService or GenericConsumerMimeLiteralPartService
 * classes instead).
 *
 * @author Zaahid Bateson
 */
abstract class AbstractGenericConsumerService extends AbstractConsumerService
{
    /**
     * Returns the regex '\s+' (whitespace) pattern matcher as a token marker so
     * the header value is split along whitespace characters.
     *
     * @return string[] an array of regex pattern matchers
     */
    protected function getTokenSeparators() : array
    {
        return ['\s+'];
    }

    /**
     * AbstractGenericConsumerService doesn't have start/end tokens, and so
     * always returns false.
     */
    protected function isEndToken(string $token) : bool
    {
        return false;
    }

    /**
     * AbstractGenericConsumerService doesn't have start/end tokens, and so
     * always returns false.
     *
     * @codeCoverageIgnore
     */
    protected function isStartToken(string $token) : bool
    {
        return false;
    }

    /**
     * Returns true if a space should be added based on the passed last and next
     * parts.
     *
     */
    private function shouldAddSpace(HeaderPart $nextPart, HeaderPart $lastPart) : bool
    {
        return (!$lastPart->ignoreSpacesAfter() || !$nextPart->ignoreSpacesBefore());
    }

    /**
     * Loops over the $parts array from the current position, checks if the
     * space should be added, then adds it to $retParts and returns.
     *
     * @param HeaderPart[] $parts
     * @param HeaderPart[] $retParts
     */
    private function addSpaceToRetParts(array $parts, array &$retParts, int $curIndex, HeaderPart &$spacePart, HeaderPart $lastPart) : static
    {
        $nextPart = $parts[$curIndex];
        if ($this->shouldAddSpace($nextPart, $lastPart)) {
            $retParts[] = $spacePart;
            $spacePart = null;
        }
        return $this;
    }

    /**
     * Checks if the passed space part should be added to the returned parts and
     * adds it.
     *
     * Never adds a space if it's the first part, otherwise only add it if
     * either part isn't set to ignore the space
     *
     * @param HeaderPart[] $parts
     * @param HeaderPart[] $retParts
     */
    private function addSpaces(array $parts, array &$retParts, int $curIndex, ?HeaderPart &$spacePart = null) : static
    {
        $lastPart = \end($retParts);
        if ($spacePart !== null && $curIndex < \count($parts) && $parts[$curIndex]->getValue() !== '' && $lastPart !== false) {
            $this->addSpaceToRetParts($parts, $retParts, $curIndex, $spacePart, $lastPart);
        }
        return $this;
    }

    /**
     * Returns true if the passed HeaderPart is a Token instance and a space.
     *
     */
    private function isSpaceToken(HeaderPart $part) : bool
    {
        return ($part instanceof Token && $part->isSpace());
    }

    /**
     * Filters out ignorable spaces between parts in the passed array.
     *
     * Spaces with parts on either side of it that specify they can be ignored
     * are filtered out.  filterIgnoredSpaces is called from within
     * processParts, and if needed by an implementing class that overrides
     * processParts, must be specifically called.
     *
     * @param HeaderPart[] $parts
     * @return HeaderPart[]
     */
    protected function filterIgnoredSpaces(array $parts)
    {
        $partsFiltered = \array_values(\array_filter($parts));
        $retParts = [];
        $spacePart = null;
        $count = \count($partsFiltered);
        for ($i = 0; $i < $count; ++$i) {
            $part = $partsFiltered[$i];
            if ($this->isSpaceToken($part)) {
                $spacePart = $part;
                continue;
            }
            $this->addSpaces($partsFiltered, $retParts, $i, $spacePart);
            $retParts[] = $part;
        }
        // ignore trailing spaces
        return $retParts;
    }

    /**
     * Overridden to combine all part values into a single string and return it
     * as an array with a single element.
     *
     * The returned IHeaderParts are all LiteralParts.
     *
     * @param \ZBateson\MailMimeParser\Header\IHeaderPart[] $parts
     * @return \ZBateson\MailMimeParser\Header\IHeaderPart[]
     */
    protected function processParts(array $parts) : array
    {
        $ret = [];
        $runningValue = '';
        $filtered = $this->filterIgnoredSpaces($parts);
        foreach ($filtered as $part) {
            if ($part instanceof CommentPart) {
                $ret[] = $this->partFactory->newLiteralPart($runningValue);
                $runningValue = '';
                $ret[] = $part;
            } else {
                $runningValue .= $part->getValue();
            }
        }
        if (!empty($runningValue)) {
            $ret[] = $this->partFactory->newLiteralPart($runningValue);
        }
        return $ret;
    }
}
