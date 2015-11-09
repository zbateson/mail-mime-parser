<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

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
     * Creates and returns a \ZBateson\MailMimeParser\Header\Part\MimeLiteral
     * out of the passed string token and returns it.
     * 
     * @param string $token
     * @param bool $isLiteral
     * @return \ZBateson\MailMimeParser\Header\Part\MimeLiteral
     */
    protected function getPartForToken($token, $isLiteral)
    {
        if (preg_match('/^\s+$/', $token)) {
            return $this->partFactory->newLiteral(' ');
        } elseif ($isLiteral) {
            return $this->partFactory->newLiteral($token);
        } else {
            return $this->partFactory->newMimeLiteral($token);
        }
    }
    
    /**
     * Overridden to combine all part values into a single string and return it
     * as an array with a single element.
     * 
     * @param ZBateson\MailMimeParser\Header\Part\Part[] $parts
     * @return ZBateson\MailMimeParser\Header\Part\Part[]
     */
    protected function processParts(array $parts)
    {
        $strValue = '';
        foreach ($parts as $part) {
            $strValue .= $part->getValue();
        }
        return [$this->partFactory->newLiteral($strValue)];
    }
}
