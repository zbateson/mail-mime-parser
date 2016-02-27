<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

/**
 * Parses a mail mime message into its component parts.  To invoke, call
 * MailMimeParser::parse.
 *
 * @author Zaahid Bateson
 */
class MessageParser
{
    /**
     * @var \ZBateson\MailMimeParser\Message the Message object that the read
     * mail mime message will be parsed into
     */
    protected $message;
    
    /**
     * @var \ZBateson\MailMimeParser\MimePartFactory the MimePartFactory object
     * used to create parts.
     */
    protected $partFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\PartStreamRegistry the PartStreamRegistry 
     * object used to register stream parts.
     */
    protected $partStreamRegistry;
    
    /**
     * Sets up the parser with its dependencies.
     * 
     * @param \ZBateson\MailMimeParser\Message $m
     * @param \ZBateson\MailMimeParser\MimePartFactory $pf
     * @param \ZBateson\MailMimeParser\PartStreamRegistry $psr
     */
    public function __construct(Message $m, MimePartFactory $pf, PartStreamRegistry $psr)
    {
        $this->message = $m;
        $this->partFactory = $pf;
        $this->partStreamRegistry = $psr;
    }
    
    /**
     * Parses the passed stream handle into the ZBateson\MailMimeParser\Message
     * object and returns it.
     * 
     * @param resource $fhandle the resource handle to the input stream of the
     *        mime message
     * @return \ZBateson\MailMimeParser\Message
     */
    public function parse($fhandle)
    {
        $this->partStreamRegistry->register($this->message->getObjectId(), $fhandle);
        $this->read($fhandle, $this->message);
        return $this->message;
    }
    
    /**
     * Reads header lines up to an empty line, adding them to the passed $part.
     * 
     * @param resource $handle the resource handle to read from
     * @param \ZBateson\MailMimeParser\MimePart $part the current part to add
     *        headers to
     */
    protected function readHeaders($handle, MimePart $part)
    {
        $header = '';
        do {
            $line = fgets($handle, 1000);
            if ($line[0] !== "\t" && $line[0] !== ' ') {
                if (!empty($header) && strpos($header, ':') !== false) {
                    $a = explode(':', $header, 2);
                    $part->setRawHeader($a[0], trim($a[1]));
                }
                $header = '';
            } else {
                $line = ' ' . ltrim($line);
            }
            $header .= rtrim($line, "\r\n");
        } while (!empty($header));
    }
    
    /**
     * Reads the content of a mime part up to a boundary, or the entire message
     * if no boundary is specified.
     * 
     * readPartContent may be called to skip to the first boundary to read its
     * headers, in which case $skipPart should be true.
     * 
     * If the end boundary is found, the method returns true.
     * 
     * @param resource $handle the input stream resource
     * @param \ZBateson\MailMimeParser\Message $message the current Message
     *        object
     * @param \ZBateson\MailMimeParser\MimePart $part the current MimePart
     *        object to load the content into.
     * @param string $boundary the MIME boundary
     * @param boolean $skipPart pass true if the intention is to read up to the
     *        beginning MIME boundary's headers
     * @return boolean if the end boundary is found
     */
    protected function readPartContent($handle, Message $message, MimePart $part, $boundary, $skipPart)
    {
        $start = ftell($handle);
        $boundaryLength = 0;
        $endBoundaryFound = false;
        do {
            $line = fgets($handle);
            if (!empty($boundary)) {
                $boundaryLength = strlen($line);
                $test = rtrim($line);
                if ($test === "--$boundary") {
                    break;
                } elseif ($test === "--$boundary--") {
                    $endBoundaryFound = true;
                    break;
                }
            }
        } while (!feof($handle));
        
        if (!$skipPart) {
            $end = ftell($handle) - $boundaryLength;
            $this->partStreamRegistry->attachPartStreamHandle($part, $message, $start, $end);
            $message->addPart($part);
        }
        return $endBoundaryFound;
    }
    
    /**
     * Finds the boundaries for the current MimePart, reads its content and
     * creates and returns the next part, setting its parent part accordingly.
     * 
     * @param resource $handle The handle to read from
     * @param \ZBateson\MailMimeParser\Message $message The current Message
     * @param \ZBateson\MailMimeParser\MimePart $part 
     * @return type
     */
    protected function readMimeMessagePart($handle, Message $message, MimePart $part)
    {
        $boundary = $part->getHeaderParameter('Content-Type', 'boundary');
        $skipPart = true;
        $parent = $part;

        if (empty($boundary) || !preg_match('~multipart/\w+~i', $part->getHeaderValue('Content-Type'))) {
            // either there is no boundary (possibly no parent boundary either) and message is read
            // till the end, or we're in a boundary already and content should be read till the parent
            // boundary is reached
            if ($part->getParent() !== null) {
                $parent = $part->getParent();
                $boundary = $parent->getHeaderParameter('Content-Type', 'boundary');
            }
            $skipPart = false;
        }
        // keep reading if an end boundary is found, to find the parent's boundary
        while ($this->readPartContent($handle, $message, $part, $boundary, $skipPart) && $parent !== null) {
            $parent = $parent->getParent();
            if ($parent !== null) {
                $boundary = $parent->getHeaderParameter('Content-Type', 'boundary');
            }
            $skipPart = true;
        }
        $nextPart = $this->partFactory->newMimePart();
        if ($parent === null) {
            $parent = $message;
        }
        $nextPart->setParent($parent);
        return $nextPart;
    }
    
    /**
     * Extracts the filename and end position of a UUEncoded part.
     * 
     * The filename is set to the passed $nextFilename parameter.  The end
     * position is returned.
     * 
     * @param resource $handle the current file handle
     * @param int &$nextMode is assigned the value of the next file mode or null
     *        if not found
     * @param string &$nextFilename is assigned the value of the next filename
     *        or null if not found
     * @param int &$end assigned the offset position within the passed resource
     *        $handle of the end of the uuencoded part
     */
    private function findNextUUEncodedPartPosition($handle)
    {
        $end = ftell($handle);
        do {
            $line = trim(fgets($handle));
            $matches = null;
            if (preg_match('/^begin [0-7]{3} .*$/', $line, $matches)) {
                fseek($handle, $end);
                break;
            }
            $end = ftell($handle);
        } while (!feof($handle));
        return $end;
    }
    
    /**
     * Reads one part of a UUEncoded message and adds it to the passed Message
     * as a MimePart.
     * 
     * The method reads up to the first 'begin' part of the message, or to the
     * end of the message if no 'begin' exists.
     * 
     * @param resource $handle
     * @param \ZBateson\MailMimeParser\Message $message
     * @return string
     */
    protected function readUUEncodedOrPlainTextPart($handle, Message $message)
    {
        $start = ftell($handle);
        $line = trim(fgets($handle));
        $end = $this->findNextUUEncodedPartPosition($handle);
        
        $part = null;
        if (preg_match('/^begin ([0-7]{3}) (.*)$/', $line, $matches)) {
            $mode = $matches[1];
            $filename = $matches[2];
            $part = $this->partFactory->newUUEncodedPart($mode, $filename);
            $this->partStreamRegistry->attachPartStreamHandle($part, $message, $start, $end);
        } else {
            $part = $this->partFactory->newNonMimePart();
            $this->partStreamRegistry->attachPartStreamHandle($part, $message, $start, $end);
        }
        $message->addPart($part);
    }
    
    /**
     * Reads the message from the input stream $handle into $message.
     * 
     * The method will loop to read headers and find and parse multipart-mime
     * message parts and uuencoded attachments (as mime-parts), adding them to
     * the passed Message object.
     * 
     * @param resource $handle
     * @param \ZBateson\MailMimeParser\Message $message
     */
    protected function read($handle, Message $message)
    {
        $part = $message;
        $this->readHeaders($handle, $message);
        $contentType = $part->getHeaderValue('Content-Type');
        $mimeVersion = $part->getHeaderValue('Mime-Version');
        do {
            if ($part === $message && $contentType === null && $mimeVersion === null) {
                $this->readUUEncodedOrPlainTextPart($handle, $message);
            } else {
                $part = $this->readMimeMessagePart($handle, $message, $part);
                $this->readHeaders($handle, $part);
            }
        } while (!feof($handle));
    }
}
