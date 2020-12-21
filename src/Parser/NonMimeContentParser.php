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
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class NonMimeContentParser extends AbstractParser
{
    /**
     * @var ParsedUUEncodedPartFactory for ParsedMimePart objects
     */
    protected $parsedUuEncodedPartFactory;

    public function __construct(
        PartBuilderFactory $pbf,
        ParsedUUEncodedPartFactory $f
    ) {
        parent::__construct($pbf);
        $this->parsedUuEncodedPartFactory = $f;
    }

    private function createUuEncodedChildPart(PartBuilder $parent, $start, $mode, $filename)
    {
        $part = $this->partBuilderFactory->newPartBuilder(
            $this->parsedUuEncodedPartFactory
        );
        $part->setStreamPartStartPos($start);
        // 'begin' line is part of the content
        $part->setStreamContentStartPos($start);
        $part->setProperty('mode', $mode);
        $part->setProperty('filename', $filename);
        $parent->addChild($part);
        return $part;
    }

    protected function parse($handle, PartBuilder $partBuilder)
    {
        $partBuilder->setStreamContentStartPos(ftell($handle));
        $part = $partBuilder;
        while (!feof($handle)) {
            $start = ftell($handle);
            $line = trim($this->readLine($handle));
            if (preg_match('/^begin ([0-7]{3}) (.*)$/', $line, $matches)) {
                $part = $this->createUuEncodedChildPart(
                    $partBuilder,
                    $start,
                    $matches[1],
                    $matches[2]
                );
            }
            $part->setStreamPartAndContentEndPos(ftell($handle));
        }
        $partBuilder->setStreamPartEndPos(ftell($handle));
    }

    protected function isSupported(PartBuilder $partBuilder)
    {
        return ($partBuilder->getParent() === null && !$partBuilder->isMime());
    }
}
