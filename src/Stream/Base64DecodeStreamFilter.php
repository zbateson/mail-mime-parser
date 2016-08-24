<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use php_user_filter;

/**
 * Unfortunately neither the built-in base64 decoder in PHP, nor the HHVM
 * implementation for their ConvertFilter seem to handle large streams
 * correctly.  There appears to be no provision for data coming in when they're
 * not split on 4 byte-chunks (each 4-byte chunk of base-64 encoded data
 * translates to 3-bytes of unencoded data).
 * 
 * encoded data).
 * 
 * @author Zaahid Bateson
 */
class Base64DecodeStreamFilter extends php_user_filter
{
    /**
     * Name used when registering with stream_filter_register.
     */
    const STREAM_FILTER_NAME = 'convert.base64-decode';
    
    /**
     * @var string Leftovers from the last incomplete line that was parsed, to
     *      be prepended to the next line read.
     */
    private $leftover = '';
    
    /**
     * Returns an array of complete lines (including line endings) from the 
     * passed $bucket object.
     * 
     * If the last line on $bucket is incomplete, it's assigned to
     * $this->leftover and prepended to the first element of the first line in
     * the next call to getLines.
     * 
     * @param object $bucket
     * @return string[]
     */
    private function getRawBytes($bucket)
    {
        $raw = preg_replace('/\s+/', '', $bucket->data);
        if (!empty($this->leftover)) {
            $raw = $this->leftover . $raw;
            $this->leftover = '';
        }
        $nLeftover = strlen($raw) % 4;
        if ($nLeftover !== 0) {
            $this->leftover = substr($raw, -$nLeftover);
            $raw = substr($raw, 0, -$nLeftover);
        }
        return $raw;
    }

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
            $bytes = $this->getRawBytes($bucket);
            $nConsumed = strlen($bucket->data);
            $consumed += $nConsumed;
            $converted = base64_decode($bytes);
            
            // $this->stream is undocumented.  It was found looking at HHVM's source code
            // for its convert.iconv.* implementation in ConvertIconFilter and explained
            // somewhat in this StackOverflow page: http://stackoverflow.com/a/31132646/335059
            // declaring a member variable called 'stream' breaks the PHP implementation (5.5.9
            // at least).
            stream_bucket_append($out, stream_bucket_new($this->stream, $converted));
        }
        return PSFS_PASS_ON;
    }
}
