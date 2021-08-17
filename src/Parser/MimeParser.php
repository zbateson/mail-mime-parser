<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMessageProxyFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxyFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;

/**
 * Reads the content of a mime part.
 *
 * @author Zaahid Bateson
 */
class MimeParser extends AbstractParser
{
    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @var PartHeaderContainerFactory
     */
    protected $partHeaderContainerFactory;

    /**
     * @var HeaderParser
     */
    protected $headerParser;

    public function __construct(
        ParserMessageProxyFactory $parserMessageProxyFactory,
        ParserMimePartProxyFactory $parserMimePartProxyFactory,
        PartBuilderFactory $pbf,
        PartHeaderContainerFactory $phcf,
        HeaderParser $hp
    ) {
        parent::__construct($parserMessageProxyFactory, $parserMimePartProxyFactory);
        $this->partBuilderFactory = $pbf;
        $this->partHeaderContainerFactory = $phcf;
        $this->headerParser = $hp;
    }

    public function canParse(PartBuilder $part)
    {
        return $part->isMime();
    }

    /**
     * 
     * @param resource $handle
     * @return string
     */
    private function readBoundaryLine($handle, ParserMimePartProxy $proxy)
    {
        $size = 2048;
        $isCut = false;
        $line = fgets($handle, $size);
        while (strlen($line) === $size - 1 && substr($line, -1) !== "\n") {
            $line = fgets($handle, $size);
            $isCut = true;
        }
        $ret = rtrim($line, "\r\n");
        $proxy->setLastLineEndingLength(strlen($line) - strlen($ret));
        return ($isCut) ? '' : $ret;
    }

    /**
     * Reads lines from the passed $handle, calling
     * $partBuilder->setEndBoundaryFound with the passed line until it returns
     * true or the stream is at EOF.
     *
     * setEndBoundaryFound returns true if the passed line matches a boundary
     * for the $partBuilder itself or any of its parents.
     *
     * Once a boundary is found, setStreamPartAndContentEndPos is called with
     * the passed $handle's read pos before the boundary and its line separator
     * were read.
     *
     * @param PartBuilder $partBuilder
     */
    private function findContentBoundary(ParserMimePartProxy $proxy)
    {
        $handle = $proxy->getMessageResourceHandle();
        // last separator before a boundary belongs to the boundary, and is not
        // part of the current part
        while (!feof($handle)) {
            $endPos = ftell($handle) - $proxy->getLastLineEndingLength();
            $line = $this->readBoundaryLine($handle, $proxy);
            if (substr($line, 0, 2) === '--' && $proxy->setEndBoundaryFound($line)) {
                $proxy->setStreamPartAndContentEndPos($endPos);
                return;
            }
        }
        $proxy->setStreamPartAndContentEndPos(ftell($handle));
        $proxy->setEof();
    }

    public function parseContent(ParserPartProxy $proxy)
    {
        $proxy->setStreamContentStartPos($proxy->getMessageResourceHandlePos());
        $this->findContentBoundary($proxy);
    }

    /**
     * Checks if the new child part is just content past the end boundary
     *
     * @param ParserProxy $proxy
     * @param PartBuilder $parent
     * @param PartBuilder $child
     */
    private function createPart(PartHeaderContainer $headerContainer, PartBuilder $child)
    {
        $parentProxy = $child->getParent();
        if ($parentProxy === null || !$parentProxy->isEndBoundaryFound()) {
            $this->headerParser->parse(
                $child->getMessageResourceHandle(),
                $headerContainer
            );
            $parserProxy = $this->parserManager->createParserProxyFor($child);
            return $parserProxy;
        } else {
            // reads content past an end boundary if there is any
            $parserProxy = $this->parserPartProxyFactory->newInstance($child, $this);
            $this->parseContent($parserProxy);
            return null;
        }
    }

    /**
     * Returns true if there are more parts
     *
     * @param PartBuilder $partBuilder
     * @return ParserPartProxy
     */
    public function parseNextChild(ParserMimePartProxy $proxy)
    {
        if ($proxy->isParentBoundaryFound()) {
            return null;
        }
        $headerContainer = $this->partHeaderContainerFactory->newInstance();
        $child = $this->partBuilderFactory->newChildPartBuilder($headerContainer, $proxy);
        return $this->createPart($headerContainer, $child);
    }
}
