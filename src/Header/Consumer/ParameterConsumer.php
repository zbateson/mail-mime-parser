<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * Reads headers separated into parameters consisting of a main value, and
 * subsequent name/value pairs - for example text/html; charset=utf-8.
 * 
 * A ParameterConsumer's parts are separated by a semi-colon.  Its name/value
 * pairs are separated with an '=' character.
 * 
 * Parts may be mime-encoded entities.  Additionally, a value can be quoted and
 * comments may exist.
 * 
 * @author Zaahid Bateson
 */
class ParameterConsumer extends GenericConsumer
{
    /**
     * Returns semi-colon and equals char as token separators.
     * 
     * @return string[]
     */
    protected function getTokenSeparators()
    {
        return [';', '='];
    }
    
    /**
     * Creates and returns a \ZBateson\MailMimeParser\Header\Part\Token out of
     * the passed string token and returns it, unless the token is an escaped
     * literal, in which case a LiteralPart is returned.
     * 
     * @param string $token
     * @param bool $isLiteral
     * @return \ZBateson\MailMimeParser\Header\Part\Part
     */
    protected function getPartForToken($token, $isLiteral)
    {
        if ($isLiteral) {
            return $this->partFactory->newLiteralPart($token);
        }
        return $this->partFactory->newToken($token);
    }
    
    /**
     * Post processing involves creating Part\LiteralPart or Part\ParameterPart
     * objects out of created Token and LiteralParts.
     * 
     * @param ZBateson\MailMimeParser\Header\Part\Part[] $parts
     * @return ZBateson\MailMimeParser\Header\Part\Part[]
     */
    protected function processParts(array $parts)
    {
        $combined = [];
        $strCat = '';
        $strName = '';
        $parts[] = $this->partFactory->newToken(';');
        foreach ($parts as $part) {
            $p = $part->getValue();
            if ($part instanceof Token) {
                if ($p === ';') {
                    if (empty($strName)) {
                        $combined[] = $this->partFactory->newMimeLiteralPart($strCat);
                    } else {
                        $combined[] = $this->partFactory->newParameterPart($strName, $strCat);
                    }
                    $strName = '';
                    $strCat = '';
                    continue;
                } elseif ($p === '=') {
                    $strName = $strCat;
                    $strCat = '';
                    continue;
                }
            }
            $strCat .= $p;
        }
        return $this->filterIgnoredSpaces($combined);
    }
}
