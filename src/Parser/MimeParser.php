<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxy;

/**
 * Reads the content of a mime part.
 *
 * @author Zaahid Bateson
 */
class MimeParser implements IParser
{
    /**
     * @var PartHeaderContainerFactory
     */
    protected $partHeaderContainerFactory;

    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @var HeaderParser
     */
    protected $headerParser;

    /**
     * @var ParserMimePartFactory for ParsedMimePart objects
     */
    protected $parserMimePartFactory;

    /**
     * @var int maintains the character length of the last line separator,
     *      typically 2 for CRLF, to keep track of the correct 'end' position
     *      for a part because the CRLF before a boundary is considered part of
     *      the boundary.
     */
    protected $lastLineSeparatorLength = 0;

    public function __construct(
        PartBuilderFactory $pbf,
        PartHeaderContainerFactory $phcf,
        HeaderParser $hp,
        ParserMimePartFactory $pmpf,
        IParserFactory $ipf
    ) {
        $this->partBuilderFactory = $pbf;
        $this->partHeaderContainerFactory = $phcf;
        $this->headerParser = $hp;
        $this->parserMimePartFactory = $pmpf;
        $this->parserMimePartFactory->setParserFactory($ipf);
    }

    /**
     * Reads a line of 2048 characters.  If the line is larger than that, the
     * remaining characters in the line are read and discarded, and only the
     * first part is returned.
     *
     * This method is identical to readLine, except it calculates the number of
     * characters that make up the line's new line characters (e.g. 2 for "\r\n"
     * or 1 for "\n") and stores it in $this->lastLineSeparatorLength.
     *
     * @param resource $handle
     * @return string
     */
    private function readBoundaryLine($handle)
    {
        $size = 2048;
        $isCut = false;
        $line = fgets($handle, $size);
        while (strlen($line) === $size - 1 && substr($line, -1) !== "\n") {
            $line = fgets($handle, $size);
            $isCut = true;
        }
        $ret = rtrim($line, "\r\n");
        $this->lastLineSeparatorLength = strlen($line) - strlen($ret);
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
    private function findContentBoundary(ParserMimePartProxy $proxy, PartBuilder $partBuilder)
    {
        $handle = $partBuilder->getMessageResourceHandle();
        // last separator before a boundary belongs to the boundary, and is not
        // part of the current part
        $start = ftell($handle);
        while (!feof($handle)) {
            $endPos = ftell($handle) - $this->lastLineSeparatorLength;
            $line = $this->readBoundaryLine($handle);
            if (substr($line, 0, 2) === '--' && $proxy->setEndBoundaryFound($line)) {
                $partBuilder->setStreamPartAndContentEndPos($endPos);
                return;
            }
        }
        $partBuilder->setStreamPartAndContentEndPos(ftell($handle));
        $proxy->setEof();
    }

    public function parseContent(ParserPartProxy $proxy)
    {
        $pb = $proxy->getPartBuilder();
        $pb->setStreamContentStartPos($pb->getMessageResourceHandlePos());
        $this->findContentBoundary($proxy, $pb);
    }

    /**
     * Checks if the new child part is just content past the end boundary
     *
     * @param ParserProxy $proxy
     * @param PartBuilder $parent
     * @param PartBuilder $child
     */
    private function createPart(ParserMimePartProxy $parentProxy, PartBuilder $child)
    {
        $headerContainer = $this->partHeaderContainerFactory->newInstance();
        if (!$parentProxy->isEndBoundaryFound()) {
            $this->headerParser->parse($child, $headerContainer);
            return $this->parserMimePartFactory->newInstance($child, $headerContainer, $parentProxy);
        } else {
            // reads content past an end boundary if there is any
            $parserProxy = $this->parserMimePartFactory->newParserMimePartProxy($child, $headerContainer, $parentProxy);
            $this->parseContent($parserProxy);
            return null;
        }
    }

    /**
     * Returns true if there are more parts
     *
     * @param PartBuilder $partBuilder
     * @return IMimePart
     */
    public function parseNextChild(ParserMimePartProxy $proxy)
    {
        if ($proxy->isParentBoundaryFound()) {
            return null;
        }
        $child = $this->partBuilderFactory->newChildPartBuilder($proxy->getPartBuilder());
        return $this->createPart($proxy, $child);
    }
}
