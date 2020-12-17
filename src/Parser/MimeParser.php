<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\PartBuilder;

/**
 * Description of MimeParser
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class MimeParser extends AbstractParser {

    /**
     * @var int maintains the character length of the last line separator,
     *      typically 2 for CRLF, to keep track of the correct 'end' position
     *      for a part because the CRLF before a boundary is considered part of
     *      the boundary.
     */
    protected $lastLineSeparatorLength = 0;

    /**
     * Reads a line of 2048 characters.  If the line is larger than that, the
     * remaining characters in the line are read and
     * discarded, and only the first part is returned.
     *
     * This method is identical to readLine, except it calculates the number of
     * characters that make up the line's new line characters (e.g. 2 for "\r\n"
     * or 1 for "\n").
     *
     * @param resource $handle
     * @param int $lineSeparatorLength
     * @return string
     */
    private function readBoundaryLine($handle, &$lineSeparatorLength = 0)
    {
        $size = 2048;
        $isCut = false;
        $line = fgets($handle, $size);
        while (strlen($line) === $size - 1 && substr($line, -1) !== "\n") {
            $line = fgets($handle, $size);
            $isCut = true;
        }
        $ret = rtrim($line, "\r\n");
        $lineSeparatorLength = strlen($line) - strlen($ret);
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
     * @param resource $handle
     * @param PartBuilder $partBuilder
     */
    private function findContentBoundary($handle, PartBuilder $partBuilder)
    {
        // last separator before a boundary belongs to the boundary, and is not
        // part of the current part
        while (!feof($handle)) {
            $endPos = ftell($handle) - $this->lastLineSeparatorLength;
            $line = $this->readBoundaryLine($handle, $this->lastLineSeparatorLength);
            if ($line !== '' && $partBuilder->setEndBoundaryFound($line)) {
                $partBuilder->setStreamPartAndContentEndPos($endPos);
                return;
            }
        }
        $partBuilder->setStreamPartAndContentEndPos(ftell($handle));
        $partBuilder->setEof();
    }

    protected function parse($handle, PartBuilder $partBuilder)
    {
        if ($partBuilder->canHaveHeaders()) {
            $this->lastLineSeparatorLength = 0;
        }
        $partBuilder->setStreamContentStartPos(ftell($handle));
        $this->findContentBoundary($handle, $partBuilder);
    }

    public function isSupported(PartBuilder $partBuilder)
    {
        return ($partBuilder->getParent() !== null || $partBuilder->isMime());
    }
}
