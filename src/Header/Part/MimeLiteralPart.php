<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MailMimeParser\Stream\Helper\CharsetConverter;

/**
 * Represents a single mime header part token, with the possibility of it being
 * MIME-Encoded as per RFC-2047.
 * 
 * MimeLiteralPart automatically decodes the value if it's encoded.
 *
 * @author Zaahid Bateson
 */
class MimeLiteralPart extends LiteralPart
{
    /**
     * @var string regex pattern matching a mime-encoded part
     */
    const MIME_PART_PATTERN = '=\?[A-Za-z\-_0-9]+\?[QBqb]\?[^\?]+\?=';
    
    /**
     * @var bool set to true to ignore spaces before this part
     */
    protected $canIgnoreSpacesBefore = false;
    
    /**
     * @var bool set to true to ignore spaces after this part
     */
    protected $canIgnoreSpacesAfter = false;
    
    /**
     * Decoding the passed token value if it's mime-encoded and assigns the
     * decoded value to a member variable. Sets canIgnoreSpacesBefore and
     * canIgnoreSpacesAfter.
     * 
     * @param string $token
     */
    public function __construct($token)
    {
        $this->value = $this->decodeMime($token);
        // preg_match returns int
        $pattern = self::MIME_PART_PATTERN;
        $this->canIgnoreSpacesBefore = (bool) preg_match("/^\s*{$pattern}/", $token);
        $this->canIgnoreSpacesAfter = (bool) preg_match("/{$pattern}\s*\$/", $token);
    }
    
    /**
     * Finds and replaces mime parts with their values.
     * 
     * The method splits the token value into an array on mime-part-patterns,
     * either replacing a mime part with its value by calling iconv_mime_decode
     * or converts the encoding on the text part by calling convertEncoding.
     * 
     * @param string $value
     * @return string
     */
    protected function decodeMime($value)
    {
        $pattern = self::MIME_PART_PATTERN;
        $value = preg_replace("/($pattern)\\s+(?=$pattern)/", '$1', $value);
        $aMimeParts = preg_split("/($pattern)/", $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        $ret = '';
        foreach ($aMimeParts as $entity) {
            $ret .= $this->decodeMatchedEntity($entity);
        }
        return $ret;
    }
    
    /**
     * Decodes a single mime-encoded entity.
     * 
     * Unfortunately, mb_decode_header fails for many charsets on PHP 5.4 and
     * PHP 5.5 (even if they're listed as supported).  iconv_mime_decode doesn't
     * support all charsets.
     * 
     * Parsing out the charset and body of the encoded entity seems to be the
     * way to go to support the most charsets.
     * 
     * @param string $entity
     * @return string
     */
    private function decodeMatchedEntity($entity)
    {
        if (preg_match("/^=\?([A-Za-z\-_0-9]+)\?([QBqb])\?([^\?]+)\?=$/", $entity, $matches)) {
            $body = $matches[3];
            if (strtoupper($matches[2]) === 'Q') {
                $body = quoted_printable_decode(str_replace('_', '=20', $body));
            } else {
                $body = base64_decode($body);
            }
            $converter = new CharsetConverter($matches[1], 'UTF-8');
            return $converter->convert($body);
        }
        return $this->convertEncoding($entity);
    }
    
    /**
     * Returns true if spaces before this part should be ignored.
     * 
     * Overridden to return $this->canIgnoreSpacesBefore which is setup in the
     * constructor.
     * 
     * @return bool
     */
    public function ignoreSpacesBefore()
    {
        return $this->canIgnoreSpacesBefore;
    }
    
    /**
     * Returns true if spaces before this part should be ignored.
     * 
     * Overridden to return $this->canIgnoreSpacesAfter which is setup in the
     * constructor.
     * 
     * @return bool
     */
    public function ignoreSpacesAfter()
    {
        return $this->canIgnoreSpacesAfter;
    }
}
