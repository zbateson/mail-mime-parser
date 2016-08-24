<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use php_user_filter;

/**
 * Unfortunately neither the built-in base64 encoder in PHP, nor the HHVM
 * implementation for their ConvertFilter seem to handle large streams
 * correctly.  There appears to be no provision for data coming in when they're
 * not split on 3 byte-chunks (each 3-byte chunk trnaslates to 4-bytes of base64
 * encoded data).
 * 
 * @author Zaahid Bateson
 */
class Base64EncodeStreamFilter extends php_user_filter
{
    /**
     * Name used when registering with stream_filter_register.
     */
    const STREAM_FILTER_NAME = 'convert.base64-encode';
    
    /**
     * Base64-encodes the passed $data string and appends it to $out.
     * 
     * @param string $data data to convert
     * @param resource $out output bucket stream
     */
    private function convertAndAppend($data, $out)
    {
        $converted = chunk_split(base64_encode($data));
        stream_bucket_append($out, stream_bucket_new($this->stream, $converted));
    }
    
    /**
     * Reads from the input bucket stream, converts, and writes the uuencoded
     * stream to $out.
     * 
     * @param resource $in input bucket stream
     * @param resource $out output bucket stream
     * @param int $consumed incremented by number of bytes read from $in
     */
    private function readAndConvert($in, $out, &$consumed)
    {
        $leftovers = '';
        $i = 0;
        while ($bucket = stream_bucket_make_writeable($in)) {
            $data = $leftovers . $bucket->data;
            $consumed += $bucket->datalen;
            $nRemain = strlen($data) % 3;
            $toConvert = $data;
            if ($nRemain === 0) {
                $leftovers = '';
            } else {
                $leftovers = substr($data, -$nRemain);
                $toConvert = substr($data, 0, -$nRemain);
            }
            $this->convertAndAppend($toConvert, $out);
        }
        if (!empty($leftovers)) {
            $this->convertAndAppend($leftovers, $out);
            $leftovers = '';
        }
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
        $this->readAndConvert($in, $out, $consumed);
        return PSFS_PASS_ON;
    }
}
