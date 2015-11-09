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
     * Constructs a MimeLiteral, decoding the value if it's mime-encoded.
     * 
     * @param string $token
     */
    public function __construct($token)
    {
        parent::__construct($token);
        $this->value = $this->decodeMime($this->value);
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
        $pattern = '=\?[A-Za-z\-0-9]+\?[QBqb]\?[^\?]+\?=';
        $value = preg_replace("/($pattern)\\s+(?=$pattern)/u", '$1', $value);
        return preg_replace_callback(
            "/$pattern/u",
            function ($matches) {
                return iconv_mime_decode($matches[0], 0, 'UTF-8');
            },
            $value
        );
    }
}
