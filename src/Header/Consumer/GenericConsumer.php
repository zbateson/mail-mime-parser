<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * A minimal implementation of AbstractConsumer defining a CommentConsumer and
 * QuotedStringConsumer as sub-consumers, and splitting tokens by whitespace.
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
     * Creates and returns a
     * \ZBateson\MailMimeParser\Header\Part\MimeLiteralPart out of the passed
     * string token and returns it.
     * 
     * @param string $token
     * @param bool $isLiteral
     * @return \ZBateson\MailMimeParser\Header\Part\MimeLiteralPart
     */
    protected function getPartForToken($token, $isLiteral)
    {
        if (preg_match('/^\s+$/', $token) && !$isLiteral) {
            return $this->partFactory->newToken(' ');
        } elseif ($isLiteral) {
            return $this->partFactory->newLiteralPart($token);
        } else {
            return $this->partFactory->newMimeLiteralPart($token);
        }
    }
    
    /**
     * Filters out ignorable spaces between parts in the passed array.
     * 
     * Spaces with parts on either side of it that specify they can be ignored
     * are filtered out.  filterIgnoredSpaces is called from within
     * processParts, and if needed by an implementing class that overrides
     * processParts, must be specifically called.
     * 
     * @param ZBateson\MailMimeParser\Header\Part\HeaderPart[] $parts
     * @return ZBateson\MailMimeParser\Header\Part\HeaderPart[]
     */
    protected function filterIgnoredSpaces(array $parts)
    {
        $retParts = [];
        $spacePart = null;
        foreach ($parts as $part) {
            if ($part instanceof Token && $part->getValue() === ' ') {
                $spacePart = $part;
                continue;
            } elseif ($spacePart !== null) {
                // never add the space if it's the first part, otherwise only add it if either part
                // isn't set to ignore the space
                $lastPart = end($retParts);
                if (($lastPart !== null) && (!$lastPart->ignoreSpacesAfter() || !$part->ignoreSpacesBefore())) {
                    $retParts[] = $spacePart;
                }
                $spacePart = null;
            }
            $retParts[] = $part;
        }
        $lastPart = end($retParts);
        if ($spacePart !== null && $lastPart !== null && !$lastPart->ignoreSpacesAfter()) {
            $retParts[] = $spacePart;
        }
        return $retParts;
    }
    
    /**
     * Overridden to combine all part values into a single string and return it
     * as an array with a single element.
     * 
     * @param ZBateson\MailMimeParser\Header\Part\HeaderPart[] $parts
     * @return ZBateson\MailMimeParser\Header\Part\HeaderPart[]
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
