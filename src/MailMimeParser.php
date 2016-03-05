<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

/**
 * Parses a MIME message into a \ZBateson\MailMimeParser\Message object.
 *
 * To invoke, call parse on a MailMimeParser object.
 * 
 * $handle = fopen('path/to/file.txt');
 * $parser = new MailMimeParser();
 * $parser->parse($handle);
 * fclose($handle);
 * 
 * @author Zaahid Bateson
 */
class MailMimeParser
{
    /**
     * @var \ZBateson\MailMimeParser\SimpleDi dependency injection container
     */
    protected $di;
    
    /**
     * Sets up the parser.
     */
    public function __construct()
    {
        $this->di = SimpleDi::singleton();
    }
    
    /**
     * Parses the passed stream handle into a ZBateson\MailMimeParser\Message
     * object and returns it.
     * 
     * Internally, the message is first copied to a temp stream (with php://temp
     * which may keep it in memory or write it to disk) and its stream is used.
     * That way if the message is too large to hold in memory it can be written
     * to a temporary file if need be.
     * 
     * @param resource $handle the resource handle to the input stream of the
     *        mime message
     * @param bool $isSmtp if set to true, treats the message as a raw message
     *        from SMTP, ending input on the first ".\r\n" it finds and
     *        replacing ".." at the beginning of a line with a single ".".
     * @return \ZBateson\MailMimeParser\Message
     */
    public function parse($handle, $isSmtp = false)
    {
        // $tempHandle is attached to $message, and closed in its destructor
        $tempHandle = fopen('php://temp', 'w+');
        $this->copyToTmpFile($tempHandle, $handle, $isSmtp);
        $parser = $this->di->newMessageParser();
        $message = $parser->parse($tempHandle);
        return $message;
    }

    /**
     * Replaces lines starting with '..' with a single dot.  Returns false if a
     * line containing a single '.' character is found signifying the last line
     * of the input stream.
     * 
     * @param string $line
     * @return boolean
     */
    private function filterSmtpLines(&$line)
    {
        if (rtrim($line, "\r\n") === '.') {
            return false;
        } elseif (strpos($line, '..') === 0) {
            $line = substr($line, 1);
        }
        return true;
    }
    
    /**
     * Copies the input stream $inHandle into the $tmpHandle resource.
     * Optionally treats the input as an SMTP input message with $isSmtp,
     * considering end of input to be the first ".\r\n" it encounters.
     * 
     * @param resource $tmpHandle the temporary resource handle
     * @param resource $inHandle the input stream resource handle
     * @param bool $isSmtp set to true if $inHandle is an SMTP input stream
     */
    protected function copyToTmpFile($tmpHandle, $inHandle, $isSmtp)
    {
        do {
            $line = fgets($inHandle);
            if ($isSmtp && !$this->filterSmtpLines($line)) {
                break;
            }
            fwrite($tmpHandle, $line);
        } while (!feof($inHandle));
        rewind($tmpHandle);
    }
}
