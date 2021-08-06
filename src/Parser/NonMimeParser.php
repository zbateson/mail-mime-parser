<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserUUEncodedPartFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;

/**
 * Reads a non-mime email message with any uuencoded child parts.
 *
 * @author Zaahid Bateson
 */
class NonMimeParser implements IParser
{
    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @var ParserUUEncodedPartFactory for ParsedMimePart objects
     */
    protected $parserUUEncodedPartFactory;

    private $nextPartStart = null;
    private $nextPartMode = null;
    private $nextPartFilename = null;

    public function __construct(
        PartBuilderFactory $pbf,
        ParserUUEncodedPartFactory $f
    ) {
        $this->partBuilderFactory = $pbf;
        $this->parserUUEncodedPartFactory = $f;
    }

    private function createUuEncodedChildPart(ParserMimePartProxy $parent, $start, $mode, $filename)
    {
        $pb = $this->partBuilderFactory->newChildPartBuilder($parent->getPartBuilder());
        $part = $this->parserUUEncodedPartFactory->newInstance($pb, $mode, $filename, $parent);
        $pb->setStreamPartStartPos($start);
        $pb->setStreamContentStartPos($start);
        return $part;
    }

    private function parseNextPart(PartBuilder $partBuilder)
    {
        $handle = $partBuilder->getMessageResourceHandle();
        while (!feof($handle)) {
            $start = ftell($handle);
            $line = trim(MessageParser::readLine($handle));
            if (preg_match('/^begin ([0-7]{3}) (.*)$/', $line, $matches)) {
                $this->nextPartStart = $start;
                $this->nextPartMode = $matches[1];
                $this->nextPartFilename = $matches[2];
                return;
            }
            $partBuilder->setStreamPartAndContentEndPos(ftell($handle));
        }
    }

    public function parseContent(ParserPartProxy $proxy)
    {
        $partBuilder = $proxy->getPartBuilder();
        $handle = $partBuilder->getMessageResourceHandle();
        if ($this->nextPartStart !== null || feof($handle)) {
            return;
        }
        if ($partBuilder->getStreamContentStartPos() === null) {
            $partBuilder->setStreamContentStartPos(ftell($handle));
        }
        $this->parseNextPart($partBuilder);
    }

    public function parseNextChild(ParserMimePartProxy $proxy)
    {
        $pb = $proxy->getPartBuilder();
        $handle = $pb->getMessageResourceHandle();
        if ($this->nextPartStart === null || feof($handle)) {
            return null;
        }
        $child = $this->createUuEncodedChildPart(
            $proxy,
            $this->nextPartStart,
            $this->nextPartMode,
            $this->nextPartFilename
        );
        $this->nextPartStart = null;
        $this->nextPartMode = null;
        $this->nextPartFilename = null;
        return $child;
    }
}
