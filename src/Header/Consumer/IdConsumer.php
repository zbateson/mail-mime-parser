<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Consumer;

/**
 * Parses a single ID from an ID header.
 *
 * Begins consuming greedily on anything except a space, to allow for
 * incorrectly-formatted ID headers.  Ends consuming only when a '>' character
 * is found.  This means an incorrectly-formatted header can't have multiple
 * IDs, but one surrounded by '<>' chars may contain spaces.
 *
 * @author Zaahid Bateson
 */
class IdConsumer extends AbstractConsumer
{
    /**
     * Returns the following as sub-consumers:
     *  - \ZBateson\MailMimeParser\Header\Consumer\CommentConsumer
     *  - \ZBateson\MailMimeParser\Header\Consumer\QuotedStringConsumer
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
     * Overridden to return patterns matching the beginning part of an ID ("<"
     * and ">\s*" chars).
     * 
     * @return string[] the patterns
     */
    public function getTokenSeparators()
    {
        return ['<', '>'];
    }
    
    /**
     * Returns true for '>'.
     */
    protected function isEndToken($token)
    {
        return ($token === '>');
    }
    
    /**
     * AddressConsumer is "greedy", so this always returns true unless the token
     * consists only of whitespace (as it would between '>' and '<' chars).
     * 
     * @param string $token
     * @return boolean false
     */
    protected function isStartToken($token)
    {
        return (preg_match('/^\s+$/', $token) !== 1);
    }
    
    /**
     * Concatenates the passed parts into a single part and returns it.
     *
     * @param \ZBateson\MailMimeParser\Header\Part\HeaderPart[] $parts
     * @return \ZBateson\MailMimeParser\Header\Part\HeaderPart[]
     */
    protected function processParts(array $parts)
    {
        $strValue = '';
        foreach ($parts as $part) {
            $strValue .= $part->getValue();
        }
        return [$this->partFactory->newLiteralPart($strValue)];
    }
}
