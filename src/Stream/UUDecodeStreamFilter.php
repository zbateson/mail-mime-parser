<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use php_user_filter;

/**
 * Stream filter converts uuencoded text to its raw binary.
 *
 * @author Zaahid Bateson
 */
class UUDecodeStreamFilter extends php_user_filter
{
    /**
     * Name used when registering with stream_filter_register.
     */
    const STREAM_FILTER_NAME = 'mailmimeparser-uudecode';
    
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
    private function getLines($bucket)
    {
        $lines = preg_split(
            '/([^\r\n]+[\r\n]+)/',
            $bucket->data,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        if ($this->leftover !== '') {
            $lines[0] = $this->leftover . $lines[0];
            $this->leftover = '';
        }
        $last = end($lines);
        if ($last[strlen($last) - 1] !== "\n") {
            $this->leftover = array_pop($lines);
        }
        return $lines;
    }
    
    /**
     * Returns true if the passed $line is empty or matches the beginning header
     * pattern for a uuencoded message.
     * 
     * @param string $line
     * @return bool
     */
    private function isEmptyOrStartLine($line)
    {
        return ($line === '' || preg_match('/^begin \d{3} .*$/', $line));
    }
    
    /**
     * Returns true if the passed $line is either a backtick character '`' or
     * the string 'end' signifying the end of the uuencoded message.
     * 
     * @param string $line
     * @return bool
     */
    private function isEndLine($line)
    {
        return ($line === '`' || $line === 'end');
    }
    
    /**
     * Filters a single line of encoded input.  Returns NULL if the end has been
     * reached.
     * 
     * @param string $line
     * @return string the decoded line
     */
    private function filterLine($line)
    {
        $cur = ltrim(rtrim($line, "\t\n\r\0\x0B"));
        if ($this->isEmptyOrStartLine($cur)) {
            return '';
        } elseif ($this->isEndLine($cur)) {
            return null;
        }
        return convert_uudecode($cur);
    }
    
    /**
     * Filters the lines in the passed $lines array, returning a concatenated
     * string of decoded lines.
     * 
     * @param array $lines
     * @param int $consumed
     * @return string
     */
    private function filterBucketLines(array $lines, &$consumed)
    {
        $data = '';
        foreach ($lines as $line) {
            $consumed += strlen($line);
            $filtered = $this->filterLine($line);
            if ($filtered === null) {
                break;
            }
            $data .= $filtered;
        }
        return $data;
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
            $lines = $this->getLines($bucket);
            $converted = $this->filterBucketLines($lines, $consumed);
            
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
