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
     * @var StreamLeftover
     */
    private $leftovers;
    
    /**
     * @var bool
     */
    private $headerWritten = false;
    
    /**
     * UUEncodes the passed $data string and appends it to $out.
     * 
     * @param string $data data to convert
     * @param resource $out output bucket stream
     */
    private function convertAndAppend($data, $out)
    {
        $converted = preg_replace('/\r\n|\r|\n/', "\r\n", convert_uuencode($data));
        $cleaned = rtrim(substr(rtrim($converted), 0, -1));      // remove end line ` character
        if (empty($cleaned)) {
            return;
        }
        $cleaned = "\r\n" . $cleaned;
        stream_bucket_append($out, stream_bucket_new($this->stream, $cleaned));
    }
    
    /**
     * Writes out the header for a uuencoded part to the passed stream resource
     * handle.
     * 
     * @param resource $out
     */
    private function writeUUEncodingHeader($out)
    {
        $data = 'begin 666 ';
        if (isset($this->params['filename'])) {
            $data .= $this->params['filename'];
        } else {
            $data .= 'null';
        }
        stream_bucket_append($out, stream_bucket_new($this->stream, $data));
    }
    
    /**
     * Returns the footer for a uuencoded part.
     * 
     * @return string
     */
    private function getUUEncodingFooter()
    {
        return "\r\n`\r\nend\r\n";
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
        while ($bucket = stream_bucket_make_writeable($in)) {
            $data = $this->leftovers->value . $bucket->data;
            if (!$this->headerWritten) {
                $this->writeUUEncodingHeader($out);
                $this->headerWritten = true;
            }
            $consumed += $bucket->datalen;
            $nRemain = strlen($data) % 45;
            $toConvert = $data;
            if ($nRemain === 0) {
                $this->leftovers->value = '';
                $this->leftovers->encodedValue = $this->getUUEncodingFooter();
            } else {
                $this->leftovers->value = substr($data, -$nRemain);
                $this->leftovers->encodedValue = "\r\n" .
                    rtrim(substr(rtrim(convert_uuencode($this->leftovers->value)), 0, -1))
                    . $this->getUUEncodingFooter();
                $toConvert = substr($data, 0, -$nRemain);
            }
            $this->convertAndAppend($toConvert, $out);
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
    
    /**
     * Sets up the leftovers object
     */
    public function onCreate()
    {
        if (isset($this->params['leftovers'])) {
            $this->leftovers = $this->params['leftovers'];
        }
    }
}
