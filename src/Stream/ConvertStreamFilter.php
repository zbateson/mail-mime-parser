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
    const STREAM_FILTER_NAME = 'mmp-convert.*';

    /**
     * @var string Leftovers from the last incomplete line that was parsed, to
     *      be prepended to the next line read.
     */
    private $leftover = '';
    
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
        // strip off 'mmp-convert.'
        $name = substr($this->filtername, 12);
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
     * Sets up a remainder of read bytes if one of the last two bytes
     * read is an '=' since quoted_printable_decode wouldn't work if one
     * read operation ends with "=3" and the next begins with "D" for
     * example.
     *
     * @param string $data
     */
    private function getFilteredBucket($data)
    {
        $ret = $this->leftover . $data;
        if ($this->fnFilterName === 'quoted_printable_decode') {
            $len = strlen($ret);
            $eq = strrpos($ret, '=');
            if (($eq !== false) && ($eq === $len - 1 || $eq === $len - 2)) {
                $this->leftover = substr($ret, $eq);
                $ret = substr($ret, 0, $eq);
            } else {
                $this->leftover = '';
            }
        }
        return $ret;
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
            $filtered = $this->getFilteredBucket($bucket->data);
            $data = call_user_func($this->fnFilterName, $filtered);
            stream_bucket_append($out, stream_bucket_new($this->stream, $data));
        }
        return PSFS_PASS_ON;
    }
}