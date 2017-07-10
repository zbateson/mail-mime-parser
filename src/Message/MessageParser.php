<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Stream\PartStreamRegistry;

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
     * @var \ZBateson\MailMimeParser\Message\MimePartFactory the MimePartFactory object
     * used to create parts.
     */
    protected $partFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Stream\PartStreamRegistry the
     *      PartStreamRegistry 
     * object used to register stream parts.
     */
    protected $partStreamRegistry;
    
    /**
     * Sets up the parser with its dependencies.
     * 
     * @param \ZBateson\MailMimeParser\Message $m
     * @param \ZBateson\MailMimeParser\Message\MimePartFactory $pf
     * @param \ZBateson\MailMimeParser\Stream\PartStreamRegistry $psr
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
     * Ensures the header isn't empty, and contains a colon character, then
     * splits it and assigns it to $part
     * 
     * @param string $header
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     */
    private function addRawHeaderToPart($header, MimePart $part)
    {
        if ($header !== '' && strpos($header, ':') !== false) {
            $a = explode(':', $header, 2);
            $part->setRawHeader($a[0], trim($a[1]));
        }
    }
    
    /**
     * Reads header lines up to an empty line, adding them to the passed $part.
     * 
     * @param resource $handle the resource handle to read from
     * @param \ZBateson\MailMimeParser\Message\MimePart $part the current part to add
     *        headers to
     */
    protected function readHeaders($handle, MimePart $part)
    {
        $header = '';
        do {
            $line = fgets($handle, 1000);
            if ($line[0] !== "\t" && $line[0] !== ' ') {
                $this->addRawHeaderToPart($header, $part);
                $header = '';
            } else {
                $line = "\r\n" . $line;
            }
            $header .= rtrim($line, "\r\n");
        } while ($header !== '');
    }
    
    /**
     * Finds the end of the Mime part at the current read position in $handle
     * and sets $boundaryLength to the number of bytes in the part, and
     * $endBoundaryFound to true if it's an 'end' boundary, meaning there are no
     * further parts for the current mime part (ends with --).
     * 
     * @param resource $handle
     * @param string $boundary
     * @param int $boundaryLength
     * @param boolean $endBoundaryFound
     */
    private function findPartBoundaries($handle, $boundary, &$boundaryLength, &$endBoundaryFound)
    {
        do {
            $line = fgets($handle);
            $boundaryLength = strlen($line);
            $test = rtrim($line);
            if ($test === "--$boundary") {
                break;
            } elseif ($test === "--$boundary--") {
                $endBoundaryFound = true;
                break;
            }
        } while (!feof($handle));
    }
    
    /**
     * Adds the part to its parent.
     * 
     * @param MimePart $part
     */
    private function addToParent(MimePart $part)
    {
        if ($part->getParent() !== null) {
            $part->getParent()->addPart($part);
        }
    }
    
    /**
     * 
     * 
     * @param type $handle
     * @param MimePart $part
     * @param Message $message
     * @param type $contentStartPos
     * @param type $boundaryLength
     */
    protected function attachStreamHandles($handle, MimePart $part, Message $message, $contentStartPos, $boundaryLength)
    {
        $end = ftell($handle) - $boundaryLength;
        $this->partStreamRegistry->attachContentPartStreamHandle($part, $message, $contentStartPos, $end);
        $this->partStreamRegistry->attachOriginalPartStreamHandle($part, $message, $part->startHandlePosition, $end);
        
        if ($part->getParent() !== null) {
            do {
                $end = ftell($handle);
            } while (!feof($handle) && rtrim(fgets($handle)) === '');
            fseek($handle, $end, SEEK_SET);
            $this->partStreamRegistry->attachOriginalPartStreamHandle(
                $part->getParent(),
                $message,
                $part->getParent()->startHandlePosition,
                $end
            );
        }
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
     * @param \ZBateson\MailMimeParser\Message\MimePart $part the current MimePart
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
        if ($boundary !== null) {
            $this->findPartBoundaries($handle, $boundary, $boundaryLength, $endBoundaryFound);
        } else {
            fseek($handle, 0, SEEK_END);
        }
        $type = $part->getHeaderValue('Content-Type', 'text/plain');
        if (!$skipPart || preg_match('~multipart/\w+~i', $type)) {
            $this->attachStreamHandles($handle, $part, $message, $start, $boundaryLength);
            $this->addToParent($part);
        }
        return $endBoundaryFound;
    }
    
    /**
     * Returns the boundary from the parent MimePart, or the current boundary if
     * $parent is null
     * 
     * @param string $curBoundary
     * @param \ZBateson\MailMimeParser\Message\MimePart $parent
     * @return string
     */
    private function getParentBoundary($curBoundary, MimePart $parent = null)
    {
        return $parent !== null ?
            $parent->getHeaderParameter('Content-Type', 'boundary') :
            $curBoundary;
    }
    
    /**
     * Instantiates and returns a new MimePart setting the part's parent to
     * either the passed $parent, or $message if $parent is null.
     * 
     * @param \ZBateson\MailMimeParser\Message $message
     * @param \ZBateson\MailMimeParser\Message\MimePart $parent
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    private function newMimePartForMessage(Message $message, MimePart $parent = null)
    {
        $nextPart = $this->partFactory->newMimePart();
        $nextPart->setParent($parent === null ? $message : $parent);
        return $nextPart;
    }
    
    /**
     * Keeps reading if an end boundary is found, to find the parent's boundary
     * and the part's content.
     * 
     * @param resource $handle
     * @param \ZBateson\MailMimeParser\Message $message
     * @param \ZBateson\MailMimeParser\Message\MimePart $parent
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     * @param string $boundary
     * @param bool $skipFirst
     * @return \ZBateson\MailMimeParser\Message\MimePart
     */
    private function readMimeMessageBoundaryParts(
        $handle,
        Message $message,
        MimePart $parent,
        MimePart $part,
        $boundary,
        $skipFirst
    ) {
        $skipPart = $skipFirst;
        while ($this->readPartContent($handle, $message, $part, $boundary, $skipPart) && $parent !== null) {
            $parent = $parent->getParent();
            // $boundary used by next call to readPartContent
            $boundary = $this->getParentBoundary($boundary, $parent);
            $skipPart = true;
        }
        return $this->newMimePartForMessage($message, $parent);
    }
    
    /**
     * Finds the boundaries for the current MimePart, reads its content and
     * creates and returns the next part, setting its parent part accordingly.
     * 
     * @param resource $handle The handle to read from
     * @param \ZBateson\MailMimeParser\Message $message The current Message
     * @param \ZBateson\MailMimeParser\Message\MimePart $part 
     * @return MimePart
     */
    protected function readMimeMessagePart($handle, Message $message, MimePart $part)
    {
        $boundary = $part->getHeaderParameter('Content-Type', 'boundary');
        $skipFirst = true;
        $parent = $part;

        if ($boundary === null || !$part->isMultiPart()) {
            // either there is no boundary (possibly no parent boundary either) and message is read
            // till the end, or we're in a boundary already and content should be read till the parent
            // boundary is reached
            if ($part->getParent() !== null) {
                $parent = $part->getParent();
                $boundary = $parent->getHeaderParameter('Content-Type', 'boundary');
            }
            $skipFirst = false;
        }
        return $this->readMimeMessageBoundaryParts($handle, $message, $parent, $part, $boundary, $skipFirst);
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
        $part = $message;
        if (preg_match('/^begin ([0-7]{3}) (.*)$/', $line, $matches)) {
            $mode = $matches[1];
            $filename = $matches[2];
            $part = $this->partFactory->newUUEncodedPart($mode, $filename);
            $message->addPart($part);
        }
        $this->partStreamRegistry->attachContentPartStreamHandle($part, $message, $start, $end);
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
        $part->startHandlePosition = 0;
        $this->readHeaders($handle, $message);
        do {
            if (!$message->isMime()) {
                $this->readUUEncodedOrPlainTextPart($handle, $message);
            } else {
                $part = $this->readMimeMessagePart($handle, $message, $part);
                $part->startHandlePosition = ftell($handle);
                $this->readHeaders($handle, $part);
            }
        } while (!feof($handle));
    }
}
