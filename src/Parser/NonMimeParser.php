<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Part\ParsedUUEncodedPartFactory;

/**
 * Reads a non-mime email message with any uuencoded child parts.
 *
 * @author Zaahid Bateson
 */
class NonMimeParser implements IContentParser, IChildPartParser
{
    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @var ParsedUUEncodedPartFactory for ParsedMimePart objects
     */
    protected $parsedUuEncodedPartFactory;

    private $nextPartStart = null;
    private $nextPartMode = null;
    private $nextPartFilename = null;

    public function __construct(
        PartBuilderFactory $pbf,
        ParsedUUEncodedPartFactory $f
    ) {
        $this->partBuilderFactory = $pbf;
        $this->parsedUuEncodedPartFactory = $f;
    }

    private function createUuEncodedChildPart(PartBuilder $parent, $start, $mode, $filename)
    {
        $part = $this->partBuilderFactory->newPartBuilder(
            $this->parsedUuEncodedPartFactory,
            $parent->getStream()
        );
        $part->setNonMimePart(true);
        $part->setStreamPartStartPos($start);
        $part->setStreamContentStartPos($this->nextPartStart);
        $part->setProperty('mode', $mode);
        $part->setProperty('filename', $filename);
        $part->setParent($parent);
        return $part;
    }

    private function parseNextPart(PartBuilder $partBuilder, ParserProxy $proxy)
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

    public function parseContent(PartBuilder $partBuilder, ParserProxy $proxy)
    {
        $handle = $partBuilder->getMessageResourceHandle();
        if ($this->nextPartStart !== null || feof($handle)) {
            return;
        }
        if ($partBuilder->getParent() === null) {
            $partBuilder->setStreamContentStartPos(ftell($handle));
        }
        $this->parseNextPart($partBuilder, $proxy);
        $proxy->updatePartContent($partBuilder);
    }

    public function parseNextChild(PartBuilder $partBuilder, ParserProxy $proxy)
    {
        $handle = $partBuilder->getMessageResourceHandle();
        if ($this->nextPartStart === null || feof($handle)) {
            return false;
        }
        $child = $this->createUuEncodedChildPart(
            $partBuilder,
            $this->nextPartStart,
            $this->nextPartMode,
            $this->nextPartFilename
        );
        $this->nextPartStart = null;
        $this->nextPartMode = null;
        $this->nextPartFilename = null;
        $proxy->updatePartChildren($child);
        return true;
    }

    public function canParse(PartBuilder $partBuilder)
    {
        return (($partBuilder->isNonMimePart())
            || ($partBuilder->getParent() === null && !$partBuilder->isMimeMessagePart()));
    }
}
