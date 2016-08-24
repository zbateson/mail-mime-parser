<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 * 
 * Idea taken from HHVM's ConvertFilter and coded to avoid requiring a
 * different license and because I don't want to deal with legalities.
 * 
 * Unfortunately the HHVM version seems to fail for larger base64-encoded files
 * (both encoding and decoding).  Once the stream needs to be buffered, if it
 * isn't buffered on a 3-byte chunk for encoding, or 4 byte-chunk for decoding,
 * it won't make sense.  Currently it is only used for quoted-printable encoding
 * and decoding.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use php_user_filter;

class ConvertStreamFilter extends php_user_filter
{
    /**
     * Name used when registering with stream_filter_register.
     */
    const STREAM_FILTER_NAME = 'convert.*';
    
    /**
     * @var string function to call for encoding/decoding
     */
    private $fnFilterName;
    
    /**
     * Sets up which function should be called.
     * 
     * @return bool
     */
    public function onCreate()
    {
        // strip off 'convert.'
        $name = substr($this->filtername, 8);
        $this->isEncode = false;
        $aFilters = [
            'quoted-printable-encode' => true,
            'quoted-printable-decode' => true,
        ];
        if (!isset($aFilters[$name])) {
            return false;
        }
        $this->fnFilterName = str_replace('-', '_', $name);
        return true;
    }
    
    /**
     * Filter implementation converts calls the relevant encode/decode filter
     * and chunk_split if needed, before returning PSFS_PASS_ON.
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
            $data = call_user_func($this->fnFilterName, $bucket->data);
            stream_bucket_append($out, stream_bucket_new($this->stream, $data));
        }
        return PSFS_PASS_ON;
    }
}