<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Part\UUEncodedPartHeaderContainerFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserNonMimeMessageProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserNonMimeMessageProxyFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserUUEncodedPartFactory;

/**
 * Reads a non-mime email message with any uuencoded child parts.
 *
 * @author Zaahid Bateson
 */
class NonMimeParser extends AbstractParser
{
    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @var UUEncodedPartHeaderContainerFactory
     */
    protected $partHeaderContainerFactory;

    public function __construct(
        ParserNonMimeMessageProxyFactory $pnmmpf,
        ParserUUEncodedPartFactory $pupf,
        PartBuilderFactory $pbf,
        UUEncodedPartHeaderContainerFactory $uephcf
    ) {
        parent::__construct($pnmmpf, $pupf);
        $this->partBuilderFactory = $pbf;
        $this->partHeaderContainerFactory = $uephcf;
    }

    public function canParse(PartBuilder $part)
    {
        return true;
    }

    private function createUuEncodedChildPart(ParserNonMimeMessageProxy $parent)
    {
        $hc = $this->partHeaderContainerFactory->newInstance($parent->getNextPartMode(), $parent->getNextPartFilename());
        $pb = $this->partBuilderFactory->newChildPartBuilder($hc, $parent);
        $proxy = $this->parserManager->createParserProxyFor($pb);
        $pb->setStreamPartStartPos($parent->getNextPartStart());
        $pb->setStreamContentStartPos($parent->getNextPartStart());
        return $proxy;
    }

    private function parseNextPart(ParserPartProxy $proxy)
    {
        $handle = $proxy->getMessageResourceHandle();
        while (!feof($handle)) {
            $start = ftell($handle);
            $line = trim(MessageParser::readLine($handle));
            if (preg_match('/^begin ([0-7]{3}) (.*)$/', $line, $matches)) {
                $proxy->setNextPartStart($start);
                $proxy->setNextPartMode($matches[1]);
                $proxy->setNextPartFilename($matches[2]);
                return;
            }
            $proxy->setStreamPartAndContentEndPos(ftell($handle));
        }
    }

    public function parseContent(ParserPartProxy $proxy)
    {
        $handle = $proxy->getMessageResourceHandle();
        if ($proxy->getNextPartStart() !== null || feof($handle)) {
            return;
        }
        if ($proxy->getStreamContentStartPos() === null) {
            $proxy->setStreamContentStartPos(ftell($handle));
        }
        $this->parseNextPart($proxy);
    }

    public function parseNextChild(ParserMimePartProxy $proxy)
    {
        $handle = $proxy->getMessageResourceHandle();
        if ($proxy->getNextPartStart() === null || feof($handle)) {
            return null;
        }
        $child = $this->createUuEncodedChildPart(
            $proxy
        );
        $proxy->clearNextPart();
        return $child;
    }
}
