<?php
namespace ZBateson\MailMimeParser;

/**
 * Implements a filter converting the stream's character encoding while reading
 * from it, so the charset of strings returned by read operations are guaranteed
 * to be encoded with mb_internal_encoding().
 * 
 * The underlying charset is set on the filtername used when creating the
 * stream with stream_filter_append - it is assumed the charset is after a '.'
 * character in the name.
 *
 * @author Zaahid Bateson
 */
class CharsetStreamFilter extends \php_user_filter
{
    /**
     * Name used when registering with stream_filter_register.
     */
    const STREAM_FILTER_NAME = 'mailmimeparser-encode.*';
    
    /**
     * @var string the character set the stream is using
     */
    protected $charset;
    
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
            $bucket->data = mb_convert_encoding($bucket->data, mb_internal_encoding(), $this->charset);
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }
    
    /**
     * Overridden to extract the charset from the filtername.  An example of a
     * filter name sent to stream_filter_append with a charset would be:
     * 
     * stream_filter_append(resource, 'mailmimeparser-encode.utf-8');
     */
    public function onCreate()
    {
        $this->charset = substr($this->filtername, strrpos($this->filtername, '.') + 1);
    }
}
