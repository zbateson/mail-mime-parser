<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use php_user_filter;

/**
 * Implements a filter converting the stream's character encoding while reading
 * from it, so the charset of strings returned by read operations are guaranteed
 * to be encoded to UTF-8.
 * 
 * The underlying charset is set on the filtername used when creating the
 * stream with stream_filter_append - it is assumed the charset is after a '.'
 * character in the name.
 *
 * @author Zaahid Bateson
 */
class CharsetStreamFilter extends php_user_filter
{
    /**
     * Name used when registering with stream_filter_register.
     */
    const STREAM_FILTER_NAME = 'mailmimeparser-encode';
    
    /**
     * @var string the character set the stream is using
     */
    protected $charset = 'ISO-8859-1';
    
    /**
     * @var array an array of additional charsets supported but not documented
     */
    private $additionalCharsets = [
        'CP850',
        'GB2312'
    ];
    
    /**
     * @var array an array of translated charsets (must be upper-case)
     */
    private $translatedCharsets = [
        'US-ASCII' => 'ASCII',
        'ISO-8859-8-I' => 'ISO-8859-8',
    ];
    
    /**
     * Filter implementation converts encoding before returning PSFS_PASS_ON.
     * 
     * @param resource $in
     * @param resource $out
     * @param int $consumed
     * @param bool $closing
     * @return int
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $converted = mb_convert_encoding($bucket->data, 'UTF-8', $this->charset);
            $consumed += strlen($bucket->data);
            
            // $this->stream is undocumented.  It was found looking at HHVM's source code
            // for its convert.iconv.* implementation in ConvertIconFilter and explained
            // somewhat in this StackOverflow page: http://stackoverflow.com/a/31132646/335059
            // declaring a member variable called 'stream' breaks the PHP implementation (5.5.9
            // at least).
            stream_bucket_append($out, stream_bucket_new($this->stream, $converted));
        }
        return PSFS_PASS_ON;
    }
    
    /**
     * Overridden to extract the charset from the params array and check if the
     * passed charset is supported or listed in the translation table in
     * CharsetStreamFilter::translatedCharsets.
     * 
     * Unfortunately __construct doesn't seem to be called for this class, so
     * setting up 'availableCharsets' in the constructor doesn't work out.
     */
    public function onCreate()
    {
        if (!empty($this->params['charset'])) {
            $this->charset = strtoupper($this->params['charset']);
        }
        $availableCharsets = array_merge(
            $this->additionalCharsets,
            array_map('strtoupper', mb_list_encodings())
        );
        if (array_key_exists($this->charset, $this->translatedCharsets)) {
            $this->charset = $this->translatedCharsets[$this->charset];
        }
        if (!in_array($this->charset, $availableCharsets)) {
            $this->charset = 'pass';
        }
    }
}
