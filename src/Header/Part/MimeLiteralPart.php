<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Part;

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
    protected $mimePartPattern = '=\?[A-Za-z\-0-9]+\?[QBqb]\?[^\?]+\?=';
    
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
        $this->canIgnoreSpacesBefore = (bool) preg_match("/^\s*{$this->mimePartPattern}/", $token);
        $this->canIgnoreSpacesAfter = (bool) preg_match("/{$this->mimePartPattern}\s*\$/", $token);
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
        $pattern = $this->mimePartPattern;
        $value = preg_replace("/($pattern)\\s+(?=$pattern)/", '$1', $value);
        $aMimeParts = preg_split("/($pattern)/", $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        $ret = '';
        foreach ($aMimeParts as $part) {
            if (preg_match("/^$pattern$/", $part)) {
                $ret .= iconv_mime_decode($part, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            } else {
                $ret .= $this->convertEncoding($part);
            }
        }
        return $ret;
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
