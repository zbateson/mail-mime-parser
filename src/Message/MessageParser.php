<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Stream\PartStreamRegistry;
use ZBateson\MailMimeParser\Message\Part\PartBuilder;
use ZBateson\MailMimeParser\Message\Part\PartBuilderFactory;
use ZBateson\MailMimeParser\Message\Part\PartFactoryService;

/**
 * Parses a mail mime message into its component parts.  To invoke, call
 * MailMimeParser::parse.
 *
 * @author Zaahid Bateson
 */
class MessageParser
{
    /**
     * @var \ZBateson\MailMimeParser\Message\Part\PartFactoryService service
     * instance used to create MimePartFactory objects.
     */
    protected $partFactoryService;
    
    /**
     * @var \ZBateson\MailMimeParser\Message\Part\PartBuilderFactory used to
     *      create PartBuilders
     */
    protected $partBuilderFactory;
    
    /**
     * @var \ZBateson\MailMimeParser\Stream\PartStreamRegistry used for
     *      registering message part streams.
     */
    protected $partStreamRegistry;
    
    /**
     * Sets up the parser with its dependencies.
     * 
     * @param \ZBateson\MailMimeParser\Message\Part\PartFactoryService $pfs
     * @param \ZBateson\MailMimeParser\Message\Part\PartBuilderFactory $pbf
     * @param PartStreamRegistry $psr
     */
    public function __construct(
        PartFactoryService $pfs,
        PartBuilderFactory $pbf,
        PartStreamRegistry $psr
    ) {
        $this->partFactoryService = $pfs;
        $this->partBuilderFactory = $pbf;
        $this->partStreamRegistry = $psr;
    }
    
    /**
     * Parses the passed stream handle into a ZBateson\MailMimeParser\Message
     * object and returns it.
     * 
     * @param resource $fhandle the resource handle to the input stream of the
     *        mime message
     * @return \ZBateson\MailMimeParser\Message
     */
    public function parse($fhandle)
    {
        $messageObjectId = uniqid();
        $this->partStreamRegistry->register($messageObjectId, $fhandle);
        $partBuilder = $this->read($fhandle);
        return $partBuilder->createMessagePart($fhandle, $messageObjectId);
    }
    
    /**
     * Ensures the header isn't empty and contains a colon separator character,
     * then splits it and calls $partBuilder->addHeader.
     * 
     * @param string $header
     * @param PartBuilder $partBuilder
     */
    private function addRawHeaderToPart($header, PartBuilder $partBuilder)
    {
        if ($header !== '' && strpos($header, ':') !== false) {
            $a = explode(':', $header, 2);
            $partBuilder->addHeader($a[0], trim($a[1]));
        }
    }
    
    /**
     * Reads header lines up to an empty line, adding them to the passed
     * $partBuilder.
     * 
     * @param resource $handle the resource handle to read from
     * @param PartBuilder $partBuilder the current part to add headers to
     */
    protected function readHeaders($handle, PartBuilder $partBuilder)
    {
        $header = '';
        do {
            $line = fgets($handle, 1000);
            if (empty($line) || $line[0] !== "\t" && $line[0] !== ' ') {
                $this->addRawHeaderToPart($header, $partBuilder);
                $header = '';
            } else {
                $line = "\r\n" . $line;
            }
            $header .= rtrim($line, "\r\n");
        } while ($header !== '');
    }
    
    /**
     * Reads lines from the passed $handle, calling $partBuilder->setEndBoundary
     * with the passed line until either setEndBoundary returns true or there
     * are no more lines to be read.
     * 
     * setEndBoundary returns true if the passed line matches a boundary for the
     * $partBuilder itself or any of its parents.
     * 
     * As lines are read, setStreamPartAndContentEndPos is called with the
     * passed $handle's read pos (ftell($handle)) to update the position of
     * content in the part.
     * 
     * If the entire stream is read and an end boundary was found, i.e.
     * $partBuilder->setEndBoundary returns true, true is returned to indicate
     * that a content boundary was found.  Otherwise false is returned.
     * 
     * @param resource $handle
     * @param PartBuilder $partBuilder
     * @return boolean true if a mime content boundary was found for
     *         $partBuilder
     */
    private function findContentBoundary($handle, PartBuilder $partBuilder)
    {
        while (!feof($handle)) {
            $partBuilder->setStreamPartAndContentEndPos(ftell($handle));
            $line = fgets($handle);
            $test = rtrim($line);
            if ($partBuilder->setEndBoundary($test)) {
                return true;
            }
        }
        $partBuilder->setStreamPartAndContentEndPos(ftell($handle));
        return false;
    }
    
    /**
     * Reads content for a non-mime message.  If there are uuencoded attachment
     * parts in the message (denoted by 'begin' lines), those parts are read and
     * added to the passed $partBuilder as children.
     * 
     * @param resource $handle
     * @param PartBuilder $partBuilder
     * @return string
     */
    protected function readUUEncodedOrPlainTextMessage($handle, PartBuilder $partBuilder)
    {
        $partBuilder->setStreamContentStartPos(ftell($handle));
        $part = $partBuilder;
        while (!feof($handle)) {
            $start = ftell($handle);
            $line = trim(fgets($handle));
            if (preg_match('/^begin ([0-7]{3}) (.*)$/', $line, $matches)) {
                $part = $this->partBuilderFactory->newPartBuilder(
                    $this->partFactoryService->getUUEncodedPartFactory()
                );
                $part->setStreamPartStartPos($start);
                // 'begin' line is part of the content
                $part->setStreamContentStartPos($start);
                $part->setProperty('mode', $matches[1]);
                $part->setProperty('filename', $matches[2]);
                $partBuilder->addChild($part);
            }
            $part->setStreamPartAndContentEndPos(ftell($handle));
        }
        $partBuilder->setStreamPartEndPos(ftell($handle));
    }
    
    /**
     * Reads content for a single part of a MIME message.
     * 
     * If the part being read is in turn a multipart part, readPart is called on
     * it recursively to read its headers and content.
     * 
     * The method tries to read content until a mime boundary (for this part for
     * a multipart, or for the parent boundary) is found or EOF is reached.
     * 
     * The start/end positions of the part's content are set on the passed
     * $partBuilder as lines are read, as is the part's end position.
     * 
     * @param resource $handle
     * @param PartBuilder $partBuilder
     */
    private function readPartContent($handle, PartBuilder $partBuilder)
    {
        $partBuilder->setStreamContentStartPos(ftell($handle));
        if ($this->findContentBoundary($handle, $partBuilder) && $partBuilder->isMultiPart()) {
            while (!feof($handle) && !$partBuilder->isEndBoundaryFound()) {
                $child = $this->partBuilderFactory->newPartBuilder(
                    $this->partFactoryService->getMimePartFactory()
                );
                $partBuilder->addChild($child);
                $this->readPart($handle, $child);
                if ($child->isEndBoundaryFound()) {
                    $discard = $this->partBuilderFactory->newPartBuilder(
                        $this->partFactoryService->getMimePartFactory()
                    );
                    $discard->setParent($partBuilder);
                    $this->findContentBoundary($handle, $discard);
                }
            }
            // for non-multipart parts, setStreamContentAndPartEndPos is called
            // in findContentBoundary
            $partBuilder->setStreamPartEndPos(ftell($handle));
        }
    }
    
    /**
     * Reads a part and any of its children, into the passed $partBuilder.
     * 
     * @param resource $handle
     * @param PartBuilder $partBuilder
     * @param boolean $isMessage
     */
    protected function readPart($handle, PartBuilder $partBuilder, $isMessage = false)
    {
        $partBuilder->setStreamPartStartPos(ftell($handle));
        $this->readHeaders($handle, $partBuilder);
        if ($isMessage && !$partBuilder->isMime()) {
            $this->readUUEncodedOrPlainTextMessage($handle, $partBuilder);
        } else {
            $this->readPartContent($handle, $partBuilder);
        }
    }
    
    /**
     * Reads the message from the input stream $handle and returns a PartBuilder
     * representing it.
     * 
     * @param resource $handle
     * @return PartBuilder
     */
    protected function read($handle)
    {
        $partBuilder = $this->partBuilderFactory->newPartBuilder(
            $this->partFactoryService->getMessageFactory()
        );
        $this->readPart($handle, $partBuilder, true);
        return $partBuilder;
    }
}
