<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

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
     * @var \ZBateson\MailMimeParser\MessageFactory the Message factory used to
     *      create the returned message
     */
    protected $messageFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\MimePartFactory the MimePartFactory object
     * used to create parts.
     */
    protected $partFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\PartBuilderFactory used to create
     * PartBuilders
     */
    protected $partBuilderFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Stream\PartStreamRegistry the
     *      PartStreamRegistry 
     * object used to register stream parts.
     */
    protected $partStreamRegistry;
    
    /**
     * Sets up the parser with its dependencies.
     * 
     * @param \ZBateson\MailMimeParser\Message\MessageFactory $mf
     * @param \ZBateson\MailMimeParser\Message\MimePartFactory $pf
     * @param \ZBateson\MailMimeParser\Message\PartBuilderFactory $pbf
     * @param PartStreamRegistry $psr
     */
    public function __construct(MessageFactory $mf, MimePartFactory $pf, PartBuilderFactory $pbf, PartStreamRegistry $psr)
    {
        $this->messageFactory = $mf;
        $this->partFactory = $pf;
        $this->partBuilderFactory = $pbf;
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
        //$this->partStreamRegistry->register($this->message->getObjectId(), $fhandle);
        $partBuilder = $this->read($fhandle);
        return $this->messageFactory->newParsedMessage($partBuilder, $fhandle);
    }
    
    /**
     * Ensures the header isn't empty, and contains a colon character, then
     * splits it and assigns it to $partBuilder
     * 
     * @param string $header
     * @param \ZBateson\MailMimeParser\Message\MimePart $partBuilder
     */
    private function addRawHeaderToPart($header, PartBuilder $partBuilder)
    {
        if ($header !== '' && strpos($header, ':') !== false) {
            $a = explode(':', $header, 2);
            $partBuilder->setRawHeader($a[0], trim($a[1]));
        }
    }
    
    /**
     * Reads header lines up to an empty line, adding them to the passed
     * $partBuilder.
     * 
     * @param resource $handle the resource handle to read from
     * @param \ZBateson\MailMimeParser\Message\MimePart $partBuilder the current part to add
     *        headers to
     */
    protected function readHeaders($handle, PartBuilder $partBuilder)
    {
        $header = '';
        do {
            $line = fgets($handle, 1000);
            if ($line[0] !== "\t" && $line[0] !== ' ') {
                $this->addRawHeaderToPart($header, $partBuilder);
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
    private function findContentBoundary($handle, PartBuilder $partBuilder)
    {
        while (!feof($handle)) {
            $partBuilder->streamContentReadEndPos = ftell($handle);
            $line = fgets($handle);
            $test = rtrim($line);
            if ($partBuilder->setEndBoundary($test)) {
                return true;
            }
        }
        $partBuilder->streamContentReadEndPos = ftell($handle);
        return false;
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
    protected function readUUEncodedOrPlainTextPart($handle, PartBuilder $partBuilder)
    {
        $start = ftell($handle);
        $line = trim(fgets($handle));
        $end = $this->findNextUUEncodedPartPosition($handle);
        if (preg_match('/^begin ([0-7]{3}) (.*)$/', $line, $matches)) {
            $mode = $matches[1];
            $filename = $matches[2];
            $child = $this->partFactory->newUUEncodedPart($mode, $filename);
            $partBuilder->addChild($child);
        }
        // $this->partStreamRegistry->attachContentPartStreamHandle($partBuilder, $start, $end);
    }
    
    private function readPartContent($handle, PartBuilder $partBuilder)
    {
        $partBuilder->streamContentReadStartPos = ftell($handle);
        if ($this->findContentBoundary($handle, $partBuilder) && $partBuilder->isMultiPart()) {
            while (!feof($handle) && !$partBuilder->isEndBoundaryFound()) {
                $child = $this->partBuilderFactory->newPartBuilder();
                $partBuilder->addChild($child);
                $this->readPart($handle, $child);
                if ($child->isEndBoundaryFound()) {
                    $discard = $this->partBuilderFactory->newPartBuilder();
                    $discard->setParent($partBuilder);
                    $this->findContentBoundary($handle, $discard);
                }
            }
        }
        $partBuilder->streamPartReadEndPos = ftell($handle);
    }
    
    protected function readPart($handle, PartBuilder $partBuilder, $isMessage = false)
    {
        $partBuilder->streamPartReadStartPos = ftell($handle);
        $this->readHeaders($handle, $partBuilder);
        if ($isMessage && !$partBuilder->isMime()) {
            $this->readUUEncodedOrPlainTextPart($handle, $partBuilder);
        } else {
            $this->readPartContent($handle, $partBuilder);
        }
    }
    
    /**
     * Reads the message from the input stream $handle into $message.
     * 
     * The method will loop to read headers and find and parse multipart-mime
     * message parts and uuencoded attachments (as mime-parts).
     * 
     * @param resource $handle
     * @return \ZBateson\MailMimeParser\PartBuilder the PartBuilder 
     */
    protected function read($handle)
    {
        $partBuilder = $this->partBuilderFactory->newPartBuilder();
        $this->readPart($handle, $partBuilder, true);
        return $partBuilder;
    }
}
