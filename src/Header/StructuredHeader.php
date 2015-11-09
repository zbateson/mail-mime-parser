<?php
namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Part\Token;

/**
 * Description of Header
 *
 * @author Zaahid Bateson
 */
class StructuredHeader extends Header implements \IteratorAggregate
{
    protected $parts = [];
    protected $consumers = [];
    protected $partGlue = '';
    
    private $tokens = [];
    
    protected function setupConsumer()
    {
        $this->consumer = null;
        $this->consumers = [
            $this->consumerService->getQuotedStringConsumer(),
            $this->consumerService->getCommentConsumer(),
            $this->consumerService->getGenericConsumer(),
        ];
    }
    
    protected function parseValue()
    {
        if (!empty($this->rawValue)) {
            $this->parts = $this->parseIntoParts($this->rawValue);
            $this->value = '';
            foreach ($this->parts as $part) {
                if (!empty($this->value)) {
                    $this->value .= $this->partGlue;
                }
                $this->value .= $part->value;
            }
            $this->value = trim($this->value);
        }
    }
    
    protected function getTokenMarkers()
    {
        $chars = '';
        foreach ($this->consumers as $consumer) {
            $chars .= implode('', $consumer->getTokenMarkers());
        }
        return $chars;
    }
    
    protected function getTokenMarkerRegex()
    {
        $sChars = $this->getTokenMarkers();
        if (empty($sChars)) {
            return '~(\\\\.)~u';
        }
        return '~(\\\\.|[' . preg_quote($sChars, '~') . '])~u';
    }
    
    protected function currentToken()
    {
        $token = current($this->tokens);
        if ($token === false) {
            return false;
        }
        if (mb_strlen($token) === 2 && $token[0] === '\\') {
            return $this->partFactory->newLiteral($this->partFactory->newToken(mb_substr($token, 1)));
        }
        return $this->partFactory->newToken($token);
    }
    
    protected function nextToken($consumer)
    {
        $token = $this->currentToken();
        foreach ($this->consumers as $con) {
            if ($con === $consumer) {
                break;
            } elseif ($token instanceof Token && $con->isStartToken($token)) {
                if (!is_null($this->consumer) || $consumer !== end($this->consumers)) {
                    return $this->consumeTokens($con);
                }
                return false;
            }
        }
        next($this->tokens);
        return $token;
    }
    
    protected function hasMoreTokens()
    {
        return (current($this->tokens) !== false);
    }
    
    protected function consumeTokens($consumer)
    {
        $part = $consumer->newPart();
        while (($token = $this->nextToken($consumer)) !== false) {
            $ret = $consumer($part, $token);
            if ($ret) {
                $part = $ret;
            } else {
                break;
            }
        }
        return $part;
    }
    
    protected function parseSinglePart()
    {
        if (!is_null($this->consumer)) {
            return $this->consumeTokens($this->consumer);
        }
        $token = $this->currentToken();
        foreach ($this->consumers as $cons) {
            if ($cons->isStartToken($token)) {
                return $this->consumeTokens($cons);
            }
        }
        return false;
    }
    
    protected function parseIntoParts($value)
    {
        $pattern = $this->getTokenMarkerRegex();
        $this->tokens = preg_split($pattern, $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $parts = [];
        while ($this->hasMoreTokens()) {
            $part = $this->parseSinglePart();
            if ($part !== false) {
                $parts[] = $part;
            }
        }
        return $parts;
    }
    
    public function getIterator()
    {
        return new \ArrayIterator($this->parts);
    }
}
