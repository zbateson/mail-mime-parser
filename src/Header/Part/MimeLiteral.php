<?php
namespace ZBateson\MailMimeParser\Header\Part;

/**
 * Represents a single mime header part token, with the possibility of it being
 * MIME-Encoded as per RFC-2047.
 * 
 * MimeLiteral automatically decodes the value if it's encoded.
 *
 * @author Zaahid Bateson
 */
class MimeLiteral extends Literal
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
     * Constructs a MimeLiteral, decoding the value if it's mime-encoded.  Sets
     * canIgnoreSpacesBefore and canIgnoreSpacesAfter.
     * 
     * @param string $token
     */
    public function __construct($token)
    {
        parent::__construct($token);
        $this->value = $this->decodeMime($this->value);
        // preg_match returns int
        $this->canIgnoreSpacesBefore = boolval(preg_match("/^\s*{$this->mimePartPattern}/u", $token));
        $this->canIgnoreSpacesAfter = boolval(preg_match("/{$this->mimePartPattern}\s*\$/u", $token));
    }
    
    /**
     * Finds and replaces mime parts with their values.
     * 
     * The method performs a regular expression match for mime-encoded parts,
     * replacing any parts it finds in the string by calling iconv_mime_decode
     * which allows the remainder of the string to contain non-ascii characters
     * which would otherwise be filtered out by iconv_mime_decode.
     * 
     * @param type $value
     * @return type
     */
    protected function decodeMime($value)
    {
        $pattern = $this->mimePartPattern;
        $value = preg_replace("/($pattern)\\s+(?=$pattern)/u", '$1', $value);
        return preg_replace_callback(
            "/$pattern/u",
            function ($matches) {
                return iconv_mime_decode($matches[0], 0, 'UTF-8');
            },
            $value
        );
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
