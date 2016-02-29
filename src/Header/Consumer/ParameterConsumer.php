<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
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
     * @return \ZBateson\MailMimeParser\Header\Part\HeaderPart
     */
    protected function getPartForToken($token, $isLiteral)
    {
        if ($isLiteral) {
            return $this->partFactory->newLiteralPart($token);
        }
        return $this->partFactory->newToken($token);
    }
    
    /**
     * Instantiates and returns either a MimeLiteralPart if $strName is empty,
     * or a ParameterPart otherwise.
     * 
     * @param string $strName
     * @param string $strValue
     * @return \ZBateson\MailMimeParser\Header\Part\MimeLiteralPart|
     *         \ZBateson\MailMimeParser\Header\Part\ParameterPart
     */
    private function getPartFor($strName, $strValue)
    {
        if (empty($strName)) {
            return $this->partFactory->newMimeLiteralPart($strValue);
        }
        return $this->partFactory->newParameterPart($strName, $strValue);
    }
    
    /**
     * Handles parameter separator tokens during final processing.
     * 
     * If the end token is found, a new HeaderPart is assigned to the passed
     * $combined array.  If an '=' character is found, $strCat is assigned to
     * $strName and emptied.
     * 
     * Returns true if the token was processed, and false otherwise.
     * 
     * @param string $tokenValue
     * @param array $combined
     * @param string $strName
     * @param string $strCat
     * @return boolean
     */
    private function processTokenPart($tokenValue, array &$combined, &$strName, &$strCat)
    {
        if ($tokenValue === ';') {
            $combined[] = $this->getPartFor($strName, $strCat);
            $strName = '';
            $strCat = '';
            return true;
        } elseif ($tokenValue === '=') {
            $strName = $strCat;
            $strCat = '';
            return true;
        }
        return false;
    }
    
    /**
     * Post processing involves creating Part\LiteralPart or Part\ParameterPart
     * objects out of created Token and LiteralParts.
     * 
     * @param \ZBateson\MailMimeParser\Header\Part\HeaderPart[] $parts
     * @return \ZBateson\MailMimeParser\Header\Part\HeaderPart[]|array
     */
    protected function processParts(array $parts)
    {
        $combined = [];
        $strCat = '';
        $strName = '';
        $parts[] = $this->partFactory->newToken(';');
        foreach ($parts as $part) {
            $pValue = $part->getValue();
            if ($part instanceof Token && $this->processTokenPart($pValue, $combined, $strName, $strCat)) {
                continue;
            }
            $strCat .= $pValue;
        }
        return $this->filterIgnoredSpaces($combined);
    }
}
