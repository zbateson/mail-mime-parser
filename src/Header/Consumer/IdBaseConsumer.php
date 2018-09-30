<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Consumer;

use Iterator;

/**
 * Serves as a base-consumer for ID headers (like Message-ID and Content-ID).
 * 
 * IdBaseConsumer passes on token processing to its sub-consumer, an
 * IdConsumer, and collects Part\LiteralPart objects processed and returned
 * by IdConsumer.
 *
 * @author Zaahid Bateson
 */
class IdBaseConsumer extends AbstractConsumer
{
    /**
     * Returns \ZBateson\MailMimeParser\Header\Consumer\IdConsumer as a
     * sub-consumer.
     * 
     * @return AbstractConsumer[] the sub-consumers
     */
    protected function getSubConsumers()
    {
        return [
            $this->consumerService->getCommentConsumer(),
            $this->consumerService->getIdConsumer()
        ];
    }
    
    /**
     * Returns '\s+' as a whitespace separator.
     * 
     * @return string[] an array of regex pattern matchers
     */
    protected function getTokenSeparators()
    {
        return ['\s+'];
    }

    /**
     * Disables advancing for start tokens not matching '<'.
     *
     * @param Iterator $tokens
     * @param bool $isStartToken
     */
    protected function advanceToNextToken(Iterator $tokens, $isStartToken)
    {
        if ($isStartToken && $tokens->current() !== '<' && $tokens->current() !== '(') {
            return;
        }
        parent::advanceToNextToken($tokens, $isStartToken);
    }

    /**
     * IdBaseConsumer doesn't have start/end tokens, and so always returns
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
     * IdBaseConsumer doesn't have start/end tokens, and so always returns
     * false.
     * 
     * @codeCoverageIgnore
     * @param string $token
     * @return boolean false
     */
    protected function isStartToken($token)
    {
        return false;
    }
    
    /**
     * Could be reached by whitespace characters.  Returns null to ignore the
     * passed token.
     * 
     * @param string $token the token
     * @param bool $isLiteral set to true if the token represents a literal -
     *        e.g. an escaped token
     * @return \ZBateson\MailMimeParser\Header\Part\HeaderPart|null the
     *         constructed header part or null if the token should be ignored
     */
    protected function getPartForToken($token, $isLiteral)
    {
        return null;
    }
}
