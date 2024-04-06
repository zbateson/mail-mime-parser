<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MailMimeParser\Header\Part\MimeToken;

/**
 * @author Zaahid Bateson
 */
class ParameterValueConsumerService extends GenericConsumerMimeLiteralPartService
{
    /**
     * Returns semi-colon and equals char as token separators.
     *
     * @return string[]
     */
    protected function getTokenSeparators() : array
    {
        return ['='];
    }
    
    /**
     * Returns true if the token is an
     */
    protected function isStartToken(string $token) : bool
    {
        return ($token === '=');
    }

    /**
     * Returns true if the token is a
     */
    protected function isEndToken(string $token) : bool
    {
        return ($token === ';');
    }

    /**
     * Overridden to use a specialized regex for finding mime-encoded parts
     * (RFC 2047).
     *
     * Some implementations seem to place mime-encoded parts within quoted
     * parameters, and split the mime-encoded parts across multiple split
     * parameters.  The specialized regex doesn't allow double quotes inside a
     * mime encoded part, so it can be "continued" in another parameter.
     *
     * @return string the regex pattern
     */
    protected function getTokenSplitPattern() : string
    {
        $sChars = \implode('|', $this->getAllTokenSeparators());
        $mimePartPattern = MimeToken::MIME_PART_PATTERN_NO_QUOTES;
        return '~(' . $mimePartPattern . '|\\\\.|' . $sChars . ')~';
    }

    /**
     * Post processing involves creating Part\LiteralPart or Part\ParameterPart
     * objects out of created Token and LiteralParts.
     *
     * @param IHeaderPart[] $parts The parsed parts.
     * @return IHeaderPart[] Array of resulting final parts.
     */
    protected function processParts(array $parts) : array
    {
        return [$this->partFactory->newContainerPart($parts)];
    }
}
