<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use php_user_filter;

/**
 * Stream filter converts uuencoded text to its raw binary.
 *
 * @author Zaahid Bateson
 */
class UUEncodeStreamFilter extends php_user_filter
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
        if (!empty($this->leftover)) {
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
     * Filters a single line of encoded input.  Returns NULL if the end has been
     * reached.
     * 
     * @param string $line
     * @return string the decoded line
     */
    private function filterLine($line)
    {
        $cur = trim($line);
        if (empty($cur) || preg_match('/^begin \d{3} .*$/', $cur)) {
            return '';
        } elseif ($cur === '`' || $cur === 'end') {
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
            $bucket->data = $this->filterBucketLines($lines, $consumed);
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }
}
