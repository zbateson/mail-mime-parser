<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * A minimal implementation of AbstractConsumer defining a CommentConsumer and
 * QuotedStringConsumer as sub-consumers, and splitting tokens by whitespace.
 *
 * Note that GenericConsumer should be instantiated with a
 * MimeLiteralPartFactory instead of a HeaderPartFactory.  Sub-classes may not
 * need MimeLiteralPartFactory instances though.
 * 
 * @author Zaahid Bateson
 */
class GenericConsumer extends AbstractConsumer
{
    /**
     * Returns \ZBateson\MailMimeParser\Header\Consumer\CommentConsumer and
     * \ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer as
     * sub-consumers.
     * 
     * @return AbstractConsumer[] the sub-consumers
     */
    protected function getSubConsumers()
    {
        return [
            $this->consumerService->getCommentConsumer(),
            $this->consumerService->getQuotedStringConsumer(),
        ];
    }
    
    /**
     * Returns the regex '\s+' (whitespace) pattern matcher as a token marker so
     * the header value is split along whitespace characters.  GenericConsumer
     * filters out whitespace-only tokens from getPartForToken.
     * 
     * The whitespace character delimits mime-encoded parts for decoding.
     * 
     * @return string[] an array of regex pattern matchers
     */
    protected function getTokenSeparators()
    {
        return ['\s+'];
    }
    
    /**
     * GenericConsumer doesn't have start/end tokens, and so always returns
     * false.
     * 
     * @param string $token
     * @return boolean false
     */
    protected function isEndToken($token)
    {
        return false;
    }
    
    /**
     * GenericConsumer doesn't have start/end tokens, and so always returns
     * false.
     * 
     * @param string $token
     * @return boolean false
     */
    protected function isStartToken($token)
    {
        return false;
    }
    
    /**
     * Checks if the passed space part should be added to the returned parts and
     * adds it.
     * 
     * Never adds a space if it's the first part, otherwise only add it if
     * either part isn't set to ignore the space
     * 
     * @param array $parts
     * @param array $retParts
     * @param \ZBateson\MailMimeParser\Header\Part\HeaderPart $spacePart
     * @param int $curIndex
     * @return boolean true if the part was added
     */
    private function checkAddFilteredSpace(array $parts, array &$retParts, &$spacePart, $curIndex)
    {
        $lastPart = end($retParts);
        $count = count($parts);
        for ($j = $curIndex; $j < $count; ++$j) {
            $next = $parts[$j];
            if ($lastPart !== null && (!$lastPart->ignoreSpacesAfter() || !$next->ignoreSpacesBefore())) {
                $retParts[] = $spacePart;
                $spacePart = null;
                return true;
            }
        }
        return false;
    }
    
    /**
     * Filters out ignorable spaces between parts in the passed array.
     * 
     * Spaces with parts on either side of it that specify they can be ignored
     * are filtered out.  filterIgnoredSpaces is called from within
     * processParts, and if needed by an implementing class that overrides
     * processParts, must be specifically called.
     * 
     * @param \ZBateson\MailMimeParser\Header\Part\HeaderPart[] $parts
     * @return \ZBateson\MailMimeParser\Header\Part\HeaderPart[]
     */
    protected function filterIgnoredSpaces(array $parts)
    {
        $retParts = [];
        $spacePart = null;
        $count = count($parts);
        for ($i = 0; $i < $count; ++$i) {
            $part = $parts[$i];
            if ($part instanceof Token && $part->isSpace()) {
                $spacePart = $part;
                continue;
            } elseif ($spacePart !== null && $part->getValue() !== '') {
                $this->checkAddFilteredSpace($parts, $retParts, $spacePart, $i);
            }
            $retParts[] = $part;
        }
        // ignore trailing spaces
        return $retParts;
    }
    
    /**
     * Overridden to combine all part values into a single string and return it
     * as an array with a single element.
     * 
     * @param \ZBateson\MailMimeParser\Header\Part\HeaderPart[] $parts
     * @return \ZBateson\MailMimeParser\Header\Part\LiteralPart[]|array
     */
    protected function processParts(array $parts)
    {
        $strValue = '';
        $filtered = $this->filterIgnoredSpaces($parts);
        foreach ($filtered as $part) {
            $strValue .= $part->getValue();
        }
        return [$this->partFactory->newLiteralPart($strValue)];
    }
}
