<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use php_user_filter;

/**
 * Stream filter converts binary streams to uuencoded text.
 *
 * @author Zaahid Bateson
 */
class UUEncodeStreamFilter extends php_user_filter
{
    /**
     * Name used when registering with stream_filter_register.
     */
    const STREAM_FILTER_NAME = 'mailmimeparser-uuencode';
    
    /**
     * UUEncodes the passed $data string and appends it to $out.
     * 
     * @param string $data data to convert
     * @param resource $out output bucket stream
     */
    private function convertAndAppend($data, $out)
    {
        $converted = convert_uuencode($data);
        $cleaned = "\r\n" . rtrim(substr(rtrim($converted), 0, -1));      // remove end line ` character
        stream_bucket_append($out, stream_bucket_new($this->stream, $cleaned));
    }
    
    /**
     * Appends the 'begin' header part of a uuencoded stream as:
     * begin 666 $filename
     * 
     * @param resource $out output bucket stream
     */
    private function appendHeader($out)
    {
        $filename = $this->params['file-name'];
        if (!empty($filename)) {
            $begin = "begin 666 $filename";
            stream_bucket_append($out, stream_bucket_new($this->stream, $begin));
        }
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
        while ($bucket = stream_bucket_make_writeable($in)) {
            $data = $leftovers . $bucket->data;
            $consumed += $bucket->datalen;
            $nRemain = strlen($data) % 45;
            $leftovers = substr($data, -$nRemain);
            $this->convertAndAppend(substr($data, 0, -$nRemain), $out, $consumed);
        }
        if (!empty($leftovers)) {
            $this->convertAndAppend($leftovers, $out, $consumed);
        }
    }
    
    /**
     * Appends the backtick and 'end' lines signifying the end of the uuencoded
     * stream.
     * 
     * @param resouce $out output bucket stream
     */
    private function appendFooter($out)
    {
        $end = "\r\n`\r\n";
        if (!empty($this->params['file-name'])) {
            $end .= "end\r\n\r\n";
        }
        stream_bucket_append($out, stream_bucket_new($this->stream, $end));
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
        $this->appendHeader($out);
        $this->readAndConvert($in, $out, $consumed);
        $this->appendFooter($out);
        return PSFS_PASS_ON;
    }
}
