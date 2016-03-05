<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use php_user_filter;

/**
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
        $nLeftover = strlen($raw) % 3;
        if ($nLeftover !== 0) {
            $this->leftover = substr($nLeftover, -$nLeftover);
        }
        return $raw;
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
    private function filterBucketBytes(array $lines, &$consumed)
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
            $bytes = $this->getRawBytes($bucket);
            $nConsumed = strlen($bucket->data);
            if ($this->leftover !== '') {
                $nConsumed -= $nConsumed - strlen(rtrim($bucket->data));
                $nConsumed -= strlen($this->leftover);
            }
            $consumed += $nConsumed;
            $converted = base64_decode($bytes);
            stream_bucket_append($out, stream_bucket_new($this->stream, $converted));
        }
        return PSFS_PASS_ON;
    }
}
