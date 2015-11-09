<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * Parses a date header into a Part\Date taking care of comment and quoted parts
 * as necessary.
 *
 * @author Zaahid Bateson
 */
class DateConsumer extends GenericConsumer
{
    /**
     * Returns a Part\Literal for the current token
     * 
     * @param string $token
     * @param bool $isLiteral
     * @return \ZBateson\MailMimeParser\Header\Part\Part
     */
    protected function getPartForToken($token, $isLiteral)
    {
        return $this->partFactory->newLiteral($token);
    }
    
    /**
     * Concatenates the passed parts and constructs a single Part\Date,
     * returning it in an array with a single element.
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
        return [$this->partFactory->newDate($strValue)];
    }
}
