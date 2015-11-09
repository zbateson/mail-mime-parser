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
     * literal, in which case a Literal is returned.
     * 
     * @param string $token
     * @param bool $isLiteral
     * @return \ZBateson\MailMimeParser\Header\Part\Part
     */
    protected function getPartForToken($token, $isLiteral)
    {
        if ($isLiteral) {
            return $this->partFactory->newLiteral($token);
        }
        return $this->partFactory->newToken($token);
    }
    
    /**
     * Post processing involves creating Part\Literal or Part\Parameter
     * objects out of created Token and Literals.
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
                        $combined[] = $this->partFactory->newLiteral($strCat);
                    } else {
                        $combined[] = $this->partFactory->newParameter($strName, $strCat);
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
        return $combined;
    }
}
